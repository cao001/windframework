<?php
/**
 * 前端控制器定义
 * 
 * 初始化系统信息,初始化请求对象、组件工厂、应用实例对象等。加载系统配置、组件配置，并进行解析。
 * @author Qiong Wu <papa0924@gmail.com> 2011-12-27
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $$Id$$
 * @package base
 */
abstract class AbstractWindFrontController {
	/**
	 * 组件配置地址，定组件工厂中组件配置的地址
	 * 
	 * @var string
	 */
	protected $components = '';
	/**
	 * request类型定义
	 * 
	 * @var string
	 */
	protected $request = '';
	/**
	 * 组件工程实例对象
	 * 
	 * @var WindFactory
	 */
	protected $factory = null;
	/**
	 * 应用配置
	 * 
	 * @var array
	 */
	protected $_config = array();
	/**
	 * 当前app名称
	 *
	 * @var string
	 */
	protected $_appName;
	/**
	 * 应用对象数组
	 *
	 * @var WindWebApplication
	 */
	private $_app = null;
	/**
	 * @var WindHandlerInterceptorChain
	 */
	private $_chain = null;
	protected $_errPage = 'error';

	/**
	 * @param string $appName 默认app名称
	 * @param Array|string $config 应用配置信息,支持为空或多应用配置
	 */
	public function __construct($appName, $config) {
		set_error_handler(array($this, '_errorHandle'), error_reporting());
		set_exception_handler(array($this, '_exceptionHandle'));
		$appName && $this->_appName = $appName;
		$this->_loadBaseLib();
		$this->factory = new WindFactory(@include (Wind::getRealPath($this->components, true)));
		$this->request = WindFactory::createInstance(Wind::import($this->request));
		if ($config) $this->initConfig($config);
	}

	/**
	 * 创建并返回应用对象实例
	 *
	 * @return WindWebApplication
	 */
	abstract protected function _createApplication();

	/**
	 * 预加载系统文件,返回预加载系统文件数据
	 * 
	 * 预加载系统文件格式如下，键值为类名=>值为类的includePath，可以是相对的（如果includePath中已经包含了该地址）
	 * 也可以是绝对地址，但不能是wind的命名空间形式的地址<pre>
	 * return array(
	 * 		'WindController' => 'web/WindController', 
	 *		'WindDispatcher' => 'web/WindDispatcher'
	 * </pre>
	 * @return void
	 * @return array
	 */
	abstract protected function _loadBaseLib();

	/**
	 * 创建并返回应用实例
	 * 
	 * @return WindWebApplication
	 */
	public function createApplication() {
		if ($this->_app === null) {
			$application = $this->_createApplication();
			/* @var $application WindWebApplication */
			if (!empty($this->_config[$this->_appName])) {
				if ($this->_appName !== 'default' && isset($this->_config['default'])) {
					$this->_config[$this->_appName] = WindUtility::mergeArray(
						$this->_config['default'], $this->_config[$this->_appName]);
				}
				$application->setConfig($this->_config[$this->_appName]);
			}
			$this->_app = $application;
		}
		return $this->_app;
	}

	/**
	 * 创建并执行当前应用,单应用访问入口
	 */
	public function run() {
		$this->_appName || $this->_appName = 'default';
		/* @var $router WindRouter */
		$router = $this->factory->getInstance('router');
		$router->route($this->request);
		$this->_run();
	}

	/**
	 * 注册过滤器,监听Application Run
	 *
	 * @param WindHandlerInterceptor $filter
	 */
	public function registeFilter($filter) {
		if (!$filter instanceof WindHandlerInterceptor) return;
		if ($this->_chain === null) {
			Wind::import("WIND:filter.WindHandlerInterceptorChain");
			$this->_chain = new WindHandlerInterceptorChain();
		}
		$this->_chain->addInterceptors($filter);
	}

	/**
	 * 注册组件对象
	 * 
	 * @param object $componentInstance
	 * @param string $componentName
	 * @param string $scope 默认值为 'application'
	 */
	public function registeComponent($componentInstance, $componentName, $scope = 'application') {
		switch ($componentName) {
			case 'request':
				$this->request = $componentInstance;
				break;
			default:
				$this->factory->registInstance($componentInstance, $componentName, $scope);
				break;
		}
	}

	/**
	 * 返回当前app应用名称
	 * 
	 * @return string
	 */
	public function getAppName() {
		return $this->_appName;
	}

	/**
	 * 返回当前的app应用
	 * 
	 * @param string $appName
	 * @return WindWebApplication
	 */
	public function getApp() {
		return $this->_app;
	}

	/**
	 * 异常处理句柄
	 *
	 * @param Exception $exception
	 */
	public function _exceptionHandle($exception) {
		restore_error_handler();
		restore_exception_handler();
		$trace = $exception->getTrace();
		if (@$trace[0]['file'] == '') {
			unset($trace[0]);
			$trace = array_values($trace);
		}
		$file = @$trace[0]['file'];
		$line = @$trace[0]['line'];
		$this->showErrorMessage($exception->getMessage(), $file, $line, $trace, 
			$exception->getCode());
	}

	/**
	 * 错误处理句柄
	 *
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 */
	public function _errorHandle($errno, $errstr, $errfile, $errline) {
		restore_error_handler();
		restore_exception_handler();
		$trace = debug_backtrace();
		unset($trace[0]["function"], $trace[0]["args"]);
		$this->showErrorMessage($errstr, $errfile, $errline, $trace, $errno);
	}

	/**
	 * 错误处理
	 * 
	 * @param string $message
	 * @param string $file 异常文件
	 * @param int $line 错误发生的行
	 * @param array $trace
	 * @param int $errorcode 错误代码
	 * @throws WindFinalException
	 */
	abstract protected function showErrorMessage($message, $file, $line, $trace, $errorcode);

	/**
	 * 创建并运行当前应用
	 * 
	 * 配合过滤链策略部署,可以通过{@see AbstractWindFrontController::registeFilter}
	 * 方法注册过滤器,当应用被执行时会判断当前时候有初始化过滤链对象,并选择是否是通过过滤链方式执行应用
	 * @return void
	 */
	protected function _run() {
		$application = $this->createApplication();
		if ($this->_chain !== null) {
			$this->_chain->setCallBack(array($application, 'run'), 
				array($application->getConfig('filters')));
			$this->_chain->getHandler()->handle();
		} else
			$application->run($application->getConfig('filters'));
		restore_error_handler();
		restore_exception_handler();
		$this->getApp()->getResponse()->sendResponse();
		$this->getApp()->getWindFactory()->executeDestroyMethod();
	}

	/**
	 * 初始化配置信息
	 *
	 * @param array $config
	 */
	protected function initConfig($config) {
		is_string($config) && $config = $this->factory->getInstance('configParser')->parse($config);
		if (isset($config['isclosed']) && $config['isclosed']) {
			if ($config['isclosed-tpl'])
				$this->_errPage = $config['isclosed-tpl'];
			else
				$this->_errPage = 'close';
			throw new Exception('Sorry, Site has been closed!');
		}
		if (!empty($config['components'])) {
			if (!empty($config['components']['resource'])) {
				$config['components'] = $this->factory->getInstance('configParser')->parse(
					Wind::getRealPath($config['components']['resource'], true, true));
			}
			$this->factory->loadClassDefinitions($config['components']);
		}
		foreach ($config['web-apps'] as $key => $value) {
			$rootPath = empty($value['root-path']) ? dirname($_SERVER['SCRIPT_FILENAME']) : Wind::getRealPath(
				$value['root-path'], false);
			Wind::register($rootPath, $key, true);
			$this->_config[$key] = $value;
		}
	}
}
?>