<?php  
namespace app\apply\home;

use app\index\controller\Home;
use think\Db;
use app\apply\Utils\IMS;

class Api extends Home {
	public function test() {
		$ims = new IMS(6, 1);
		return json($ims->getIMSFileContent());
	}
}