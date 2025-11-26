<?php
class Context extends ContextCore
{
public function isAndroidDevice() {
	$u_agent = $_SERVER['HTTP_USER_AGENT'];
  	if (preg_match('/Android/i', $u_agent)) {
    	return true;
    }
   	else{
		return false;
   }
}
public function isIOS() {
	$u_agent = $_SERVER['HTTP_USER_AGENT'];
  	if (preg_match('/iPhone|iPad|iPod/i', $u_agent)) {
    	return true;
    }
   	else{
		return false;
   }
}
public function isCordovaApp() {
	$u_agent = $_SERVER['HTTP_USER_AGENT'];
     
	if (preg_match('/wv/i', $u_agent)) {
    	$b1 = true;
    }
   	else{
		$b1 = false;
   	}
   	if (preg_match('/Android/i', $u_agent)) {
    
		if (preg_match('/Version/i', $u_agent)) {
     		$b2 = true; 
    	}
   		else{
			$b2 = false;
   		}   
   }
   else{
		$b2 = false;
   }
 	if (preg_match('/AppleWebKit/i', $u_agent)) {
    
		if (preg_match('/Safari/i', $u_agent)) {
     		$b3 = false; 
    	}
   		else{
			$b3 = true;
   		}   
   }
   else{
		$b3 = false;
   }
	
	return ($b1 || $b2 || $b3);
}
public function isAppiOS()
{
	return ($this->isIOS() && $this->isCordovaApp());
}
    /*
    * module: pagecache
    * date: 2024-05-21 08:49:56
    * version: 9.3.2
    */
    public function getMobileDetect()
    {
        if ($this->mobile_detect === null) {
            if (!Module::isEnabled('pagecache') || !file_exists(_PS_MODULE_DIR_ . 'pagecache/pagecache.php')) {
                return parent::getMobileDetect();
            } else {
                require_once _PS_MODULE_DIR_ . 'pagecache/pagecache.php';
                if ($this->mobile_detect === null) {
                    if (PageCache::isCacheWarmer()) {
                        $this->mobile_detect = new JprestaUtilsMobileDetect();
                    } else {
                        return parent::getMobileDetect();
                    }
                }
            }
        }
        return $this->mobile_detect;
    }
}