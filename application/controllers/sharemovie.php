<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sharemovie extends CI_Controller {

	public function __construct()
    {
      	parent::__construct();
        // Your own constructor code
        //$this->load->database('sharemovie');
        $this->movapikey='49b35ae23cb2dce9b78b40d209149e28';
        
    }

    private function getuserid($id)
	{
		
		$query = $this->db->query("select user_id from users where fb_id=".$this->db->escape($id));
		$result = $query->result();
		if($result)
		{
			foreach($result as $row)
			{
				$userid=$row->user_id;
			} 
			return $userid;
		}
		else
		{
			return false;
		}
	}
	private function groupuservalidation($id,$grpid)
	{
		
		$query = $this->db->query("select user_id from groupuser where user_id=".$this->db->escape($id)." and group_id=".$this->db->escape($grpid));
		
		if($query -> num_rows() == 1)
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	private function grpLimitValidation($id)
	{
		
		$query = $this->db->query("select group_id from groups where actv_f='Y' and created_user_id=".$this->db->escape($id));
		
		if($query -> num_rows() >= 30)
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	private function memberLimitValidation($grpid)
	{
		
		$query = $this->db->query("select user_id from groupuser where group_id=".$this->db->escape($grpid));
		
		if($query -> num_rows() >= 30)
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	private function movieLimitValidation($grpid)
	{
		
		$query = $this->db->query("select movie_id from groupmovie where group_id=".$this->db->escape($grpid));
		
		if($query -> num_rows() >= 200)
		{
			return true;
		}
		else
		{
			return false;
		}

	}


	public function login($accessToken)
	{

		$this->load->database('sharemovie');
		header('Content-type: application/json');


		// facebook url
		$service_url = 'https://graph.facebook.com/v2.4/me?access_token='.$accessToken.'&fields=id,name';

		//make the api call and store the response
		$curl = curl_init($service_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$curl_response = curl_exec($curl);
		curl_close($curl);

		//if the api call is failed
		if ($curl_response === false) {
		    //$info = curl_getinfo($curl);
		    //curl_close($curl);
		    //die('error occured during curl exec. Additioanl info: ' . var_export($info));
		    echo json_encode(array('error'=>'Unable to reach facebook servers'));
		    $this->db->close();
		    exit;

		}
		$decoded = json_decode($curl_response);
		
		//if the api call is success but error from facebook
		if (isset($decoded->error)) {
			//echo 'error';
		    //die('error occured: ' . $decoded->response->errormessage);
		    echo($curl_response);
		    $this->db->close();
		    exit;
		}

		$id = $decoded->id;
		$name = $decoded->name;
		//$pic = $decoded->picture->data->url;
		//echo $id;


		//insert a new record if it is a new user

		$query = $this->db->query("select * from users where fb_id=".$this->db->escape($id));

	    if($query -> num_rows() == 1)
	   	{
	     	$query = $this->db->query("update users set name=".$this->db->escape($name)." where fb_id=".$this->db->escape($id)); 

	     	//$userid = $this->getuserid($id);
		     
		      if($query)
		      {
		        echo json_encode(array('success'=>'User successfully logged in'));
		        $this->db->close();
		        exit;
		      }
		      else
		      {
		        echo json_encode(array('error'=>'Unable to execute query!'));
		        $this->db->close();
		        exit;
		      }
	   	}
	   	else
	   	{
		     $query = $this->db->query("insert into users(fb_id,name,crte_ts) 
		     values(".$this->db->escape($id).",".$this->db->escape($name).",CURRENT_TIMESTAMP)"); 
		     
		     //$userid = $this->getuserid($id);

		      if($query)
		      {
		        echo json_encode(array('success'=>'User successfully logged in'));		
		        $this->db->close();	        
		        exit;
		      }
		      else
		      {
		        echo json_encode(array('error'=>'Unable to execute query!'));
		        $this->db->close();
		        exit;
		      }
		 
	   	}

	/***************** END OF FUNCTION *****************/	
	}	

	public function getusergroups($id)
	{

		$this->load->database('sharemovie');
     	header('Content-type: application/json');

     	$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}
     	
     	$query = $this->db->query("select a.group_id,a.name,b.cnt from 
		(select g.group_id,name from groups g join groupuser gu on g.group_id=gu.group_id 
		where gu.user_id=".$this->db->escape($id).")a
		join 
		(select g.group_id,count(*) cnt from groups g join groupuser gu on g.group_id=gu.group_id 
		group by g.group_id)b
		on a.group_id=b.group_id order by a.name");
	     	
     		
    	$result = $query->result();
    	$output = array();
				foreach($result as $row)
				{
					array_push($output,array('group_id'=>$row->group_id,
					'group_name'=>$row->name,
					'member_cnt'=>$row->cnt
					));
				} 
		echo json_encode(array('output'=>$output));
		$this->db->close();
		exit;
		
	/***************** END OF FUNCTION *****************/
	}

	public function creategroup($id,$name)
	{

		$this->load->database('sharemovie');
		header('Content-type: application/json');

		$name=urldecode($name);

		$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}

     	$totalgrp = $this->grpLimitValidation($id);

     	if($totalgrp==true)
     	{
     		echo json_encode(array('success'=>'Maximum limit reached'));
		    $this->db->close();
		    exit;
     	}


		$query1 = $this->db->query("insert into groups(name,created_user_id,crte_ts,actv_f) 
	    values(".$this->db->escape($name).",".$this->db->escape($id).",CURRENT_TIMESTAMP,'Y')");

	    $query = $this->db->query("select group_id from groups where created_user_id="
	    .$this->db->escape($id)." order by crte_ts desc LIMIT 1");

	    $result = $query->result();

	    if($result)
		{
			foreach($result as $row)
			{
				$groupid=$row->group_id;
			} 
		}

	    $query2 = $this->db->query("insert into groupadmin(group_id,admin_user_id) 
	    values(".$this->db->escape($groupid).",".$this->db->escape($id).")"); 

	    $query3 = $this->db->query("insert into groupuser(group_id,user_id,actv_f) 
	    values(".$this->db->escape($groupid).",".$this->db->escape($id).",'Y')"); 

	    if($query1&&$query2&&$query3)
	    {
	    	echo json_encode(array('success'=>'Group created successfully'));
	    	$this->db->close();
	    	exit;
	    }
	    else
	    {
	    	echo json_encode(array('error'=>'Unable to execute query!'));
	    	$this->db->close();
	    	exit;
	    }


	/***************** END OF FUNCTION *****************/
	}


	public function searchmovie($id,$movName)
	{
		
		$this->load->database('sharemovie');
		header('Content-type: application/json');

		$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}

		$service_url = 'https://api.themoviedb.org/3/search/movie?api_key='.$this->movapikey.'&query='.$movName.'&page=1';

		//make the api call and store the response
		$curl = curl_init($service_url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$curl_response = curl_exec($curl);
		
		//if the api call is failed
		if ($curl_response === false) {
		    //$info = curl_getinfo($curl);
		    curl_close($curl);
		    //die('error occured during curl exec. Additioanl info: ' . var_export($info));
		    echo json_encode(array('error'=>'unable to get information from moviedb server'));
		    $this->db->close();
		    exit;

		}
		curl_close($curl);
		$decoded = json_decode($curl_response);

		$output = array();

		for($i=0;$i<count($decoded->results);$i++)
		{
			array_push($output,array('id'=>$decoded->results[$i]->id,
			'title'=>$decoded->results[$i]->title,
			'overview'=>$decoded->results[$i]->overview,
			'poster_path'=>$decoded->results[$i]->poster_path,
			//'poster_path'=>'',
			'release_date'=>$decoded->results[$i]->release_date));
		}

		//echo json_encode($output);
		echo json_encode(array('output'=>$output));
		$this->db->close();
		exit;
	
	/***************** END OF FUNCTION *****************/
	}

	public function addmovie($id,$movid,$name,$year,$image,$grpid)
	{
	
		$this->load->database('sharemovie');
		header('Content-type: application/json');

		$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}

     	if($this->groupuservalidation($id,$grpid)==false)
     	{
     		echo json_encode(array('error'=>'User does not belong to this group!'));
		    $this->db->close();
		    exit;
     	}

     	$totalmov = $this->movieLimitValidation($id);

     	if($totalmov==true)
     	{
     		echo json_encode(array('success'=>'Maximum limit reached'));
		    $this->db->close();
		    exit;
     	}

		//check if new movie
		$name=urldecode($name);
		$query = $this->db->query("select * from movies where movie_id=".$this->db->escape($movid));

	    if($query -> num_rows() == 1)
	   	{
	     	
	   	}
	   	else
	   	{
		     $query = $this->db->query("insert into movies(movie_id,name,image,year) 
		     values(".$this->db->escape($movid).",".$this->db->escape($name).",".$this->db->escape($image).",".$this->db->escape($year).")"); 
		     
	   	}

	   	//check if movie exits for group

	    $query = $this->db->query("select * from groupmovie 
	    where group_id=".$this->db->escape($grpid)." and movie_id=".$this->db->escape($movid)); 

	    if($query -> num_rows() == 1)
	   	{
	     	echo json_encode(array('success'=>'Movie already shared to group'));
	     	$this->db->close();
	    	exit;
	   	}
	   	else
	   	{
		     $query = $this->db->query("insert into groupmovie(group_id,user_id,movie_id,crte_ts) 
		     	values(".$this->db->escape($grpid).",".$this->db->escape($id).",".$this->db->escape($movid).",CURRENT_TIMESTAMP)"); 

		     $query = $this->db->query("insert into groupmovievote(group_id,user_id,movie_id) 
		     	values(".$this->db->escape($grpid).",".$this->db->escape($id).",".$this->db->escape($movid).")"); 
		     
	   	}
	    

	    if($query)
	    {
	    	echo json_encode(array('success'=>'Movie shared to group successfully'));
	    	$this->db->close();
	    	exit;
	    }
	    else
	    {
	    	echo json_encode(array('error'=>'Unable to execute query!'));
	    	$this->db->close();
	    	exit;
	    }
			
		

	/***************** END OF FUNCTION *****************/
	}

	public function getgroupmovies($id,$grpid)
	{
	
		$this->load->database('sharemovie');
		header('Content-type: application/json');

		$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}

     	if($this->groupuservalidation($id,$grpid)==false)
     	{
     		echo json_encode(array('error'=>'User does not belong to this group!'));
		    $this->db->close();
		    exit;
     	}

     	$query = $this->db->query("select m.movie_id,m.name,m.image,m.year,u.name as shared_by,unix_timestamp(gm.crte_ts) as timestamp,gmv.cnt as votes from movies m 
			join groupmovie gm on m.movie_id=gm.movie_id 
			join users u on gm.user_id=u.user_id
			join (select group_id,movie_id,COUNT(*) CNT from groupmovievote group by group_id,movie_id)gmv
			on gmv.group_id=gm.group_id and gmv.movie_id=gm.movie_id
			where gm.group_id=".$this->db->escape($grpid)." order by gmv.cnt desc");
	     	
    	$result = $query->result();
    	$output = array();
		foreach($result as $row)
		{
			array_push($output,array('movie_id'=>$row->movie_id,
			'movie_name'=>$row->name,
			'image'=>$row->image,
			'year'=>$row->year,
			'shared_by'=>$row->shared_by,
			'votes'=>$row->votes,
			'timestamp'=>$row->timestamp
			));
		} 
		echo json_encode(array('output'=>$output));
		$this->db->close();
	    exit;
			
		

	/***************** END OF FUNCTION *****************/
	}

	public function getgroupmembers($id,$grpid)
	{
		$this->load->database('sharemovie');
		header('Content-type: application/json');

		$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}

     	if($this->groupuservalidation($id,$grpid)==false)
     	{
     		echo json_encode(array('error'=>'User does not belong to this group!'));
		    $this->db->close();
		    exit;
     	}

     	$query = $this->db->query("select u.name,u.fb_id from groupuser gu
			join users u on gu.user_id=u.user_id
			where gu.group_id=".$this->db->escape($grpid));
	     	
    	$result = $query->result();
    	$output = array();
		foreach($result as $row)
		{
			array_push($output,array('name'=>$row->name,
			'id'=>$row->fb_id));
		} 
		echo json_encode(array('output'=>$output));
		$this->db->close();
		exit;	

	/***************** END OF FUNCTION *****************/
	}

	public function addmember($id,$fbid,$grpid)
	{
		$this->load->database('sharemovie');
		header('Content-type: application/json');

		$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}

     	if($this->groupuservalidation($id,$grpid)==false)
     	{
     		echo json_encode(array('error'=>'User does not belong to this group!'));
		    $this->db->close();
		    exit;
     	}

     	$totalmem = $this->memberLimitValidation($grpid);

     	if($totalmem==true)
     	{
     		echo json_encode(array('success'=>'Maximum limit reached'));
		    $this->db->close();
		    exit;
     	}

		$fbid = $this->getuserid($fbid);

		if($fbid==false)
     	{
     		echo json_encode(array('success'=>'User data missing in database!'));
		    $this->db->close();
		    exit;
     	}

	   	//check if member exits for group

	    $query = $this->db->query("select * from groupuser 
	    where group_id=".$this->db->escape($grpid)." 
	    and user_id=".$this->db->escape($fbid)); 

	    if($query -> num_rows() == 1)
	   	{
	     	echo json_encode(array('success'=>'Member already added to group'));
	     	$this->db->close();
	    	exit;
	   	}
	   	else
	   	{
		     $query = $this->db->query("insert into groupuser(group_id,user_id,actv_f) 
		     values(".$this->db->escape($grpid).",".$this->db->escape($fbid).",'Y')"); 
		     
	   	}
	    

	    if($query)
	    {
	    	echo json_encode(array('success'=>'Member added to group successfully'));
	    	$this->db->close();
	    	exit;
	    }
	    else
	    {
	    	echo json_encode(array('error'=>'Unable to execute query!'));
	    	$this->db->close();
	    	exit;
	    }

	/***************** END OF FUNCTION *****************/
	}

	public function votemovie($id,$movid,$grpid)
	{
		$this->load->database('sharemovie');
		header('Content-type: application/json');
	   	
	   	$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}

     	if($this->groupuservalidation($id,$grpid)==false)
     	{
     		echo json_encode(array('error'=>'User does not belong to this group!'));
		    $this->db->close();
		    exit;
     	}

	   	//check if vote exists

	    $query = $this->db->query("select * from groupmovievote 
	    where group_id=".$this->db->escape($grpid)." and movie_id=".$this->db->escape($movid)." and user_id=".$this->db->escape($id)); 

	    if($query -> num_rows() == 1)
	   	{
	     	echo json_encode(array('success'=>'You have already voted for the movie'));
	    	$this->db->close();
	    	exit;
	   	}
	   	else
	   	{
	
		     $query = $this->db->query("insert into groupmovievote(group_id,user_id,movie_id) 
		     values(".$this->db->escape($grpid).",".$this->db->escape($id).",".$this->db->escape($movid).")"); 
		     
	   	}
	    

	    if($query)
	    {
	    	echo json_encode(array('success'=>'You have voted for the movie successfully'));
	    	$this->db->close();
	    	exit;
	    }
	    else
	    {
	    	echo json_encode(array('error'=>'Unable to execute query!'));
	    	$this->db->close();
	    	exit;
	    }

	/***************** END OF FUNCTION *****************/
	}

	public function exitgroup($id,$grpid)
	{
		$this->load->database('sharemovie');
		header('Content-type: application/json');

		$id = $this->getuserid($id);

     	if($id==false)
     	{
     		echo json_encode(array('error'=>'Unable to authenticate!'));
		    $this->db->close();
		    exit;
     	}

     	if($this->groupuservalidation($id,$grpid)==false)
     	{
     		echo json_encode(array('error'=>'User does not belong to this group!'));
		    $this->db->close();
		    exit;
     	}

 
	     $query = $this->db->query("delete from groupuser where 
	     group_id=".$this->db->escape($grpid)." and user_id=".$this->db->escape($id)); 
	     
	     $query = $this->db->query("update groups set actv_f='N' where 
	     group_id=".$this->db->escape($grpid)); 
	    
	    if($query)
	    {
	    	echo json_encode(array('success'=>'You have exited the group'));
	    	$this->db->close();
	    	exit;
	    }
	    else
	    {
	    	echo json_encode(array('error'=>'Unable to execute query!'));
	    	$this->db->close();
	    	exit;
	    }

	/***************** END OF FUNCTION *****************/
	}



/***************** END OF CLASS *****************/
}


