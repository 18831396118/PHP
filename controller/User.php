<?php
namespace app\index\controller;
use think\Controller;
use RedisClient;


//namespace app\index\controller\Redis;
class User extends Controller
{
	//用户添加
    public function create()
    {
    	return view('/user/create');
    }


    //用户插入
    public function store()
    {
    	// echo '嘻嘻嘻嘻嘻嘻嘻嘻寻寻寻';

    	//提取参数
    	$data = $_POST;
    	//文件上传
    	$file = request() ->file('pic');

		// 移动到框架应用根目录/public/uploads/ 目录下
		if($file){
			//图片存储路径
			$path = ROOT_PATH . 'public' . DS . 'uploads';
		    $info = $file->move($path);
		    if($info){
		        // 成功上传后 获取上传信息
		        $data['pic'] = '/uploads/'.$info->getSaveName();

		    }else{
		        // 上传失败获取错误信息
		        echo $file->getError();die;
	        }
	    }
	    //var_dump($data);

	    $data['created_at'] = time();
	    //插入到redis中
	    $redis = RedisClient::getInstance();


	    //将数据写入到hash中
	    
	    $user_id = $redis -> incr('user_id');
	    $data['id'] = $user_id;
	    $key = 'user:'.$user_id;
	    $res = $redis -> hmset($key, $data);
	    // var_dump($res);


	    //将数据id写入到列表中
	    $res2 = $redis -> rpush('user_ids', $user_id);

	    if ($res && $res2) {
	    	$this -> success('添加成功', '/user', 3);
	    }else{
	    	$this ->error('添加失败');
	    }

    }


    //用户列表
    public function index()
    {
    	//读取用户id
    	$ids = RedisClient::getInstance()->lrange('user_ids', 0,9);

    	//遍历读取用户的信息
    	$users = [];
    	foreach ($ids as $key => $value){
    		//拼接用户到键名
    		$key = 'user:'.$value;
    		$user = RedisClient::getInstance() ->hmget($key, ['id','username','password','pic','create_at']);
    		// var_dump($user);

    		$users[] = $user;
   		 }

    return view('user/index', ['users' =>$users]);
	}



	//修改
	public function edit($id)
	{
		$key = 'user:'.$id;
		$users = RedisClient::getInstance() ->hmget($key, ['id','username','password','pic','create_at']);
		return view('/user/edit',['users' =>$users]);
	}


	//
	public function update($id)
	{
		$data = $_POST;
    	//文件上传
    	$file = request() ->file('pic');
    	$key = 'user:'.$id;
		// 移动到框架应用根目录/public/uploads/ 目录下
		if($file){
			//图片存储路径
			$path = ROOT_PATH . 'public' . DS . 'uploads';
		    $info = $file->move($path);
		    if($info){
		        // 成功上传后 获取上传信息
		        $data['pic'] = '/uploads/'.$info->getSaveName();
		    }else{
		        // 上传失败获取错误信息
		        echo $file->getError();die;
	        }
		}

		$redis = RedisClient::getInstance();
	 	
	    //将数据进行更改
	    $res2 = $redis -> hset($key,'username',$data['username']);
	    if ($res2 ==0) {
	    	$this -> success('修改成功', '/user', 3);
	    }else{
	    	$this ->error('修改失败');
	    }
	}



	//删除
	public function delete()
	{
		$id = $_GET['id'];
		$key = 'user:'.$id;
		$redis = RedisClient::getInstance();
		$res = $redis ->del($key);
		$res2 = $redis -> lrem('user_ids',$id);
		if ($res && $res2) {
			$this -> success('删除成功','/user',3);
		}else{
			$this -> error('删除失败');
		}
	}
}