<?php
/**
 * the last known user to change this file in the repository  <$LastChangedBy$>
 * @author Qian Su <aoxue.1988.su.qian@163.com>
 * @version $Id$ 
 * @package 
 * tags
 */
L::import('WIND:component.cache.stored.AWindCache');
/**
 * xcache加速缓存
 * the last known user to change this file in the repository  <$LastChangedBy$>
 * @author Qian Su <aoxue.1988.su.qian@163.com>
 * @version $Id$ 
 * @package 
 */
class WindXCache extends AWindCache {
	/* 
	 * @see wind/component/cache/base/IWindCache#set()
	 */
	public function set($key, $value, $expires = 0, IWindCacheDependency $denpendency = null) {
		return xcache_set($this->buildSecurityKey($key), $this->storeData($value, $expires, $denpendency), $expires);
	}
	/* 
	 * @see wind/component/cache/base/IWindCache#fetch()
	 */
	public function get($key) {
		return $this->getDataFromMeta($key, unserialize(xcache_get($this->buildSecurityKey($key))));
	}
	
	/* 
	 * @see wind/component/cache/base/IWindCache#batchFetch()
	 */
	public function batchGet(array $keys) {
		$data = array();
		foreach ($keys as $key) {
			$data[$key] = $this->fetch($key);
		}
		return $data;
	}
	
	/* 
	 * @see wind/component/cache/base/IWindCache#delete()
	 */
	public function delete($key) {
		return xcache_unset($this->buildSecurityKey($key));
	}
	
	/* 
	 * @see wind/component/cache/base/IWindCache#batchDelete()
	 */
	public function batchDelete(array $keys) {
		foreach ($keys as $key) {
			$this->delete($key);
		}
		return true;
	}
	
	/* 
	 * @see wind/component/cache/base/IWindCache#flush()
	 */
	public function flush() {
		for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
			if (false === xcache_clear_cache(XC_TYPE_VAR, $i)) {
				return false;
			}
		}
		return true;
	}
}