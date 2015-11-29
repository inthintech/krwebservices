<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sharemovie extends CI_Controller {

	public function __construct()
    {
      	parent::__construct();
        // Your own constructor code
        $this->load->database('sharemovie');
        $this->movapikey='49b35ae23cb2dce9b78b40d209149e28';
        
    }

    public function testservicecall()
    {
    	echo 'Connected';
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

	public function login($accessToken)
	{
		if(isset($accessToken))
		{	
			header('Content-type: application/json');
			// facebook url
			$service_url = 'https://graph.facebook.com/v2.4/me?access_token='.$accessToken.'&fields=id,name,picture';

			//make the api call and store the response
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$curl_response = curl_exec($curl);
			
			//if the api call is failed
			if ($curl_response === false) {
			    //$info = curl_getinfo($curl);
			    curl_close($curl);
			    //die('error occured during curl exec. Additioanl info: ' . var_export($info));
			    echo json_encode(array('error'=>'Unable to reach facebook servers'));
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			$id = $decoded->id;
			$name = $decoded->name;
			$pic = $decoded->picture->data->url;
			//echo $id;


			//insert a new record if it is a new user

			$query = $this->db->query("select * from users where fb_id=".$this->db->escape($id));

		    if($query -> num_rows() == 1)
		   	{
		     	$query = $this->db->query("update users set name=".$this->db->escape($name).",profile_pic_url=".
		     	$this->db->escape($pic)." where fb_id=".$this->db->escape($id)); 
			     
			      if($query)
			      {
			        
			        $userid = $this->getuserid($id);
			        if($userid)
			        {
			        	echo json_encode(array('success'=>$userid));
			        }
			        else
			        {
			        	echo json_encode(array('error'=>'Unable to reach app server'));
			        	exit;
			        }
			      }
			      else
			      {
			        echo json_encode(array('error'=>'Unable to reach app server'));
			        exit;
			      }
		   	}
		   	else
		   	{
			     $query = $this->db->query("insert into users(fb_id,name,profile_pic_url,crte_ts) 
			     values(".$this->db->escape($id).",".$this->db->escape($name).",".$this->db->escape($pic).",CURRENT_TIMESTAMP)"); 
			     
			      if($query)
			      {
			        $userid = $this->getuserid($id);
			        if($userid)
			        {
			        	echo json_encode(array('success'=>$userid));
			        }
			        else
			        {
			        	echo json_encode(array('error'=>'Unable to reach app server'));
			        	exit;
			        }
			        
			      }
			      else
			      {
			        echo json_encode(array('error'=>'Unable to reach app server'));
			        exit;
			      }
		   	}
		}
	}

	public function creategroup($id,$name)
	{
		if(isset($id)&&isset($name))
		{	

				$name=urldecode($name);
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
			    }
			    else
			    {
			    	echo json_encode(array('error'=>'Unable to reach app server'));
			    	exit;
			    }
			
			
		}

	}

	public function getmovielist($movName)
	{
		
		if(isset($movName))
		{	
			header('Content-type: application/json');
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
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			//echo($curl_response);

			//echo($decoded['id']);

			//echo count($decoded->results);

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

		}
	}

	public function addmovie($id,$movid,$name,$year,$image,$grpid)
	{
		if(isset($id)&&isset($movid)&&isset($name)&&isset($year)&&isset($image)&&isset($grpid))
		{	
			
				
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
			    }
			    else
			    {
			    	echo json_encode(array('error'=>'Unable to reach app server'));
			    	exit;
			    }
			
		}

	}

	public function votemovie($id,$movid,$grpid)
	{
		if(isset($id)&&isset($movid)&&isset($grpid))
		{	
			
			   	//check if vote exists

			    $query = $this->db->query("select * from groupmovievote 
			    where group_id=".$this->db->escape($grpid)." and movie_id=".$this->db->escape($movid)." and user_id=".$this->db->escape($id)); 

			    if($query -> num_rows() == 1)
			   	{
			     	echo json_encode(array('success'=>'You have already voted for the movie'));
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
			    }
			    else
			    {
			    	echo json_encode(array('error'=>'Unable to reach app server'));
			    	exit;
			    }
			
		}

	}

	public function removemovie($movid,$grpid)
	{
		if(isset($movid)&&isset($grpid))
		{	

				//check if new movie

				$query = $this->db->query("delete from groupmovie where movie_id=".$this->db->escape($movid)." and 
					group_id=".$this->db->escape($grpid) );

			    if($query)
			    {
			    	echo json_encode(array('success'=>'Movie removed successfully'));
			    }
			    else
			    {
			    	echo json_encode(array('error'=>'Unable to reach app server'));
			    	exit;
			    }
			
		}

	}

	public function addmember($fbid,$grpid)
	{
		if(isset($fbid)&&isset($grpid))
		{	

			$query = $this->db->query("select user_id from users where fb_id=".$this->db->escape($fbid));
			$result = $query->result();
			if($result)
			{
				foreach($result as $row)
				{
					$memid=$row->user_id;
				} 
			}
			
		   	//check if member exits for group

		    $query = $this->db->query("select * from groupuser 
		    where group_id=".$this->db->escape($grpid)." 
		    and user_id=".$this->db->escape($memid)); 

		    if($query -> num_rows() == 1)
		   	{
		     	echo json_encode(array('error'=>'Member already added'));
		    	exit;
		   	}
		   	else
		   	{
			     $query = $this->db->query("insert into groupuser(group_id,user_id,actv_f) 
			     	values(".$this->db->escape($grpid).",".$this->db->escape($memid).",'Y')"); 
			     
		   	}
		    

		    if($query)
		    {
		    	echo json_encode(array('success'=>'Member added successfully'));
		    }
		    else
		    {
		    	echo json_encode(array('error'=>'Unable to reach app server'));
		    	exit;
		    }
			
		}

	}


	public function removemember($memid,$grpid)
	{
		if(isset($memid)&&isset($grpid))
		{	
		     
		     $query = $this->db->query("delete from groupuser 
		     	where group_id=".$this->db->escape($grpid)." and user_id=".$this->db->escape($memid));
		     	
		    if($query)
		    {
		    	echo json_encode(array('success'=>'Member removed successfully'));
		    }
		    else
		    {
		    	echo json_encode(array('error'=>'Unable to reach app server'));
		    	exit;
		    }
			
		}

	}

	public function leavegroup($accessToken,$grpid)
	{
		if(isset($accessToken)&&isset($grpid))
		{	
			$id = $this->getuserid($accessToken);
			if($id)
			{
		     
		     	//check if user is admin of group

					$query = $this->db->query("select * from groupadmin 
			    where group_id=".$this->db->escape($grpid)." 
			    and admin_user_id=".$this->db->escape($id)); 

			    if($query -> num_rows() == 1)
			   	{
			     	echo json_encode(array('error'=>'Admins cannot exit the group'));
			    	exit;
			   	}
			   	else
			   	{
				     $query = $this->db->query("delete from groupuser 
			     	where group_id=".$this->db->escape($grpid)." and user_id=".$this->db->escape($id));
			     	
				    if($query)
				    {
				    	echo json_encode(array('success'=>'You have left the group'));
				    }
				    else
				    {
				    	echo json_encode(array('error'=>'Unable to reach app server'));
				    	exit;
				    }
				}
			}
			else
			{
				echo json_encode(array('fberror'=>'Unable to reach app server'));
			    exit;
			}
			
		}

	}


	public function searchmember($accessToken)
	{
		if(isset($accessToken))
		{	
			header('Content-type: application/json');
			// facebook url
			$service_url = 'https://graph.facebook.com/v2.4/me/friends?access_token='.$accessToken.'&fields=id,name,picture';

			//make the api call and store the response
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$curl_response = curl_exec($curl);
			
			//if the api call is failed
			if ($curl_response === false) {
			    //$info = curl_getinfo($curl);
			    curl_close($curl);
			    //die('error occured during curl exec. Additioanl info: ' . var_export($info));
			    echo json_encode(array('error'=>'Unable to reach facebook servers'));
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			echo($curl_response);
		   	
		}
	}

	public function getmovieinfo($movId)
	{
		
		if(isset($movId))
		{	
			header('Content-type: application/json');
			$service_url = 'https://api.themoviedb.org/3/movie/'.$movId.'?api_key='.$this->movapikey;

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
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			//echo($curl_response);

			//echo($decoded['id']);

			//echo count($decoded->results);

			$output = array();

			//for($i=0;$i<count($decoded->results);$i++)
			//{
				
				$genre = '';

				for($j=0;$j<count($decoded->genres);$j++)
				{
					if($j==0)
					{
						$genre = $decoded->genres[$j]->name;
					}
					else
					{
						$genre = $genre.', '.$decoded->genres[$j]->name;
					}
					
				}

				array_push($output,array('id'=>$decoded->id,
				'title'=>$decoded->title,
				'overview'=>$decoded->overview,
				'poster_path'=>$decoded->poster_path,
				//'poster_path'=>'',
				'release_date'=>$decoded->release_date,
				'genres'=>$genre
				));
			//}

			//echo json_encode($output);
			echo json_encode(array('output'=>$output));

		}

		else
		{
			show_404();
		}
	}

	public function getmygroups($id)
	{
		if(isset($id))
		{	

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

			
		}

	}

	public function getgroupmovies($grpid)
	{
		if(isset($grpid))
		{	
		
	     	$query = $this->db->query("select m.movie_id,m.name,m.image,m.year,u.name as shared_by,gmv.cnt as votes from movies m 
				join groupmovie gm on m.movie_id=gm.movie_id 
				join users u on gm.user_id=u.user_id
				join (select group_id,movie_id,COUNT(*) CNT from groupmovievote group by group_id,movie_id)gmv
				on gmv.group_id=gm.group_id and gmv.movie_id=gm.movie_id
				where gm.group_id=".$this->db->escape($grpid));
		     	
		    	$result = $query->result();
		    	$output = array();
						foreach($result as $row)
						{
							array_push($output,array('movie_id'=>$row->movie_id,
							'movie_name'=>$row->name,
							'image'=>$row->image,
							'year'=>$row->year,
							'shared_by'=>$row->shared_by,
							'votes'=>$row->votes
							));
						} 
						echo json_encode(array('output'=>$output));
			
		}

	}


/* End of class */
}


/*

<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Captaincool extends CI_Controller {

	
	public function index()
	{
		
		show_404();
		
	}

	public function __construct()
    {
      	parent::__construct();
        // Your own constructor code
        $this->load->model('captaincooldata','',TRUE);
    }

	public function validateuser($accessToken)
	{
		
		if(isset($accessToken))
		{	
			header('Content-type: application/json');
			// facebook url
			$service_url = 'https://graph.facebook.com/v2.4/me?access_token='.$accessToken.'&fields=id';

			//make the api call and store the response
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$curl_response = curl_exec($curl);
			
			//if the api call is failed
			if ($curl_response === false) {
			    //$info = curl_getinfo($curl);
			    curl_close($curl);
			    //die('error occured during curl exec. Additioanl info: ' . var_export($info));
			    echo json_encode(array('error'=>'unable to get information from facebook'));
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			$id = $decoded->id;
			//echo $id;


			//insert a new record if it is a new user
			if($this->captaincooldata->userStatus($id))
			{
				echo json_encode(array('success'=>$id));
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			
		}
		else
		{
			show_404();
		}
	}

	public function getmystats($fbId)
	{
		
		if(isset($fbId))
		{	
			header('Content-type: application/json');
			
			//get userid

			$result = $this->captaincooldata->getUserId($fbId);
			if($result)
			{
				foreach($result as $row)
				{
					$uid = $row->user_id;
				} 
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			$output = array();

			$result = $this->captaincooldata->getMyStats($uid);

			if($result)
			{
				foreach($result as $row)
				{
					array_push($output,array('played'=>$row->played,
				'won'=>$row->won,
				'ratio'=>$row->ratio.' %'));
				}
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			//echo json_encode($output);
			echo json_encode(array('output'=>$output));
			
		}
		else
		{
			show_404();
		}
	}

	public function getmyteam($fbId)
	{
		
		if(isset($fbId))
		{	
			header('Content-type: application/json');
			
			//get userid

			$result = $this->captaincooldata->getUserId($fbId);
			if($result)
			{
				foreach($result as $row)
				{
					$uid = $row->user_id;
				} 
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			$output = array();

			$result = $this->captaincooldata->getMyTeam($uid);

			if($result)
			{
				foreach($result as $row)
				{
					array_push($output,array('id'=>$row->player_id,
				'name'=>$row->player_name,
				'batting'=>$row->bat_pos,
				'bowling'=>$row->bowl_type));
				}
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			//echo json_encode($output);
			echo json_encode(array('output'=>$output));
			
		}
		else
		{
			show_404();
		}
	}

	public function getplayers($fbId)
	{
		
		if(isset($fbId))
		{	
			header('Content-type: application/json');
			
			//get userid

			$result = $this->captaincooldata->getUserId($fbId);
			if($result)
			{
			
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			$output = array();

			$result = $this->captaincooldata->getPlayers();

			if($result)
			{
				foreach($result as $row)
				{
					array_push($output,array(		
				'id'=>$row->player_id,
				'name'=>$row->player_name,
				'batting'=>$row->bat_pos,
				'bowling'=>$row->bowl_type));
				}
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			//echo json_encode($output);
			echo json_encode(array('output'=>$output));
			
		}
		else
		{
			show_404();
		}
	}

	public function updateteam($fbId,$p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8,$p9,$p10,$p11)
	{
		
		if(isset($fbId))
		{	
			header('Content-type: application/json');
			
			//get userid

			$result = $this->captaincooldata->getUserId($fbId);
			if($result)
			{
				foreach($result as $row)
				{
					$uid = $row->user_id;
				} 
					
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			$output = array();

			$result = $this->captaincooldata->updateteam($uid,$p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8,$p9,$p10,$p11);

			if($result)
			{
				echo json_encode(array('success'=>'Team updated successfully.'));
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}
			
		}
		else
		{
			show_404();
		}
	}

	public function removemovie($fbId,$movId)
	{
		
		if(isset($fbId)&&isset($movId))
		{	
			header('Content-type: application/json');
			
			//get userid

			$result = $this->captaincooldata->getUserId($fbId);
			if($result)
			{
				foreach($result as $row)
				{
					$id = $row->user_id;
				} 
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}


			//delete movie from user's collection

			if($this->captaincooldata->removeMovie($id,$movId))
			{
				echo json_encode(array('success'=>'Movie successfully removed'));
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			
		}
		else
		{
			show_404();
		}
	}

	public function getmovielist($movName)
	{
		
		if(isset($movName))
		{	
			header('Content-type: application/json');
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
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			//echo($curl_response);

			//echo($decoded['id']);

			//echo count($decoded->results);

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

		}

		else
		{
			show_404();
		}
	}

	public function getmylibrary($fbId)
	{
		
		if(isset($fbId))
		{	
			header('Content-type: application/json');

			//get userid

			$result = $this->captaincooldata->getUserId($fbId);
			if($result)
			{
				foreach($result as $row)
				{
					$id = $row->user_id;
				} 
			}
			else
			{
				echo json_encode(array('error'=>'unable to get information from database'));
			    exit;
			}

			//output array

			$output = array();


			//get movie data

			$result = $this->captaincooldata->getLibrary($id);
			if($result)
			{
				foreach($result as $row)
				{
					array_push($output,array(
						'id'=>$row->movie_id,
						'title'=>$row->movie_name,
						'release_year'=>$row->movie_year,
						'rating'=>$row->user_rating,
						'timestamp'=>$row->crte_ts
					));

				} 
			}
			else
			{
				//echo json_encode(array('error'=>'unable to get information from database'));
			    //exit;
			}

			//echo json_encode($output);
			echo json_encode(array('output'=>$output));

		}

		else
		{
			show_404();
		}
	}

	public function getmovieinfo($movId)
	{
		
		if(isset($movId))
		{	
			header('Content-type: application/json');
			$service_url = 'https://api.themoviedb.org/3/movie/'.$movId.'?api_key='.$this->movapikey;

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
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			//echo($curl_response);

			//echo($decoded['id']);

			//echo count($decoded->results);

			$output = array();

			//for($i=0;$i<count($decoded->results);$i++)
			//{
				
				$genre = '';

				for($j=0;$j<count($decoded->genres);$j++)
				{
					if($j==0)
					{
						$genre = $decoded->genres[$j]->name;
					}
					else
					{
						$genre = $genre.', '.$decoded->genres[$j]->name;
					}
					
				}

				array_push($output,array('id'=>$decoded->id,
				'title'=>$decoded->title,
				'overview'=>$decoded->overview,
				'poster_path'=>$decoded->poster_path,
				//'poster_path'=>'',
				'release_date'=>$decoded->release_date,
				'genres'=>$genre
				));
			//}

			//echo json_encode($output);
			echo json_encode(array('output'=>$output));

		}

		else
		{
			show_404();
		}
	}

	public function getsociallibrary($fbId,$accessToken)
	{
		
		if(isset($accessToken)&&isset($fbId))
		{	
			header('Content-type: application/json');

			$service_url = 'https://graph.facebook.com/v2.4/me/friends?limit=5000&access_token='.$accessToken.'&fields=id';

			//make the api call and store the response
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$curl_response = curl_exec($curl);
			
			//if the api call is failed
			if ($curl_response === false) {
			    //$info = curl_getinfo($curl);
			    curl_close($curl);
			    //die('error occured during curl exec. Additioanl info: ' . var_export($info));
			    echo json_encode(array('error'=>'unable to get information from facebook'));
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			//echo($curl_response);

			$fbidlist = '\''.$fbId.'\'';

			for($i=0;$i<count($decoded->data);$i++)
			{

				$fbidlist = $fbidlist.','.'\''.$decoded->data[$i]->id.'\'';
				
			}

			$output = array();

			$myIdResult = $this->captaincooldata->getUserId($fbId);
			if($myIdResult)
			{
				foreach($myIdResult as $row)
				{
					$myUserId = $row->user_id; 
				} 
			}

			$result = $this->captaincooldata->getSocialLibrary($fbidlist,$myUserId);
			if($result)
			{
				foreach($result as $row)
				{
					array_push($output,array(
						'id'=>$row->movie_id,
						'title'=>$row->movie_name,
						'release_year'=>$row->movie_year,
						'count'=>$row->count,
						//'rating'=>$row->user_rating,
						'timestamp'=>$row->crte_ts,
						'status'=>$row->status
					));

				} 
			}

			//echo json_encode($output);
			echo json_encode(array('output'=>$output));
			

		}

		else
		{
			show_404();
		}
	}

	public function getstatistics($token)
	{
		
		if(isset($token)&&$token=='sr457462554')
		{	
			header('Content-type: application/json');

			$result = $this->captaincooldata->getstatistics();
			$output = array();

			if($result)
			{
				foreach($result as $row)
				{
					array_push($output,array(
						'user_count'=>$row->user_cnt,
						'movie_count'=>$row->movie_cnt,
						'user_movie_count'=>$row->user_movie_cnt
					));

				} 
			}

			//echo json_encode($output);
			echo json_encode(array('output'=>$output));
			

		}

		else
		{
			show_404();
		}
	}

	public function getviewlist($fbId,$movId,$accessToken)
	{
		
		if(isset($accessToken)&&isset($movId)&&isset($fbId))
		{	
			header('Content-type: application/json');

			$service_url = 'https://graph.facebook.com/v2.4/me/friends?limit=5000&access_token='.$accessToken.'&fields=id,name,picture';

			//make the api call and store the response
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$curl_response = curl_exec($curl);
			
			//if the api call is failed
			if ($curl_response === false) {
			    //$info = curl_getinfo($curl);
			    curl_close($curl);
			    //die('error occured during curl exec. Additioanl info: ' . var_export($info));
			    echo json_encode(array('error'=>'unable to get information from facebook'));
			    exit;

			}
			curl_close($curl);
			$decoded = json_decode($curl_response);
			
			//if the api call is success but error from facebook
			if (isset($decoded->error)) {
				//echo 'error';
			    //die('error occured: ' . $decoded->response->errormessage);
			    echo($curl_response);
			    exit;
			}

			//get the fbids of users who added the movie

			$myview = 0;
			$viewlist = array();

			$result = $this->captaincooldata->getViews($movId);
			if($result)
			{
				foreach($result as $row)
				{
					if($row->fb_id==$fbId)
					{
						$myview = 1;
					}
					array_push($viewlist,$row->fb_id);
				} 
			}

			//get the name and picture of users who added the movie

			$output = array();

			
				for($i=0;$i<count($viewlist);$i++)
				{
					for($j=0;$j<count($decoded->data);$j++)
					{
						if($viewlist[$i]==$decoded->data[$j]->id)
						{
							array_push($output,array(
							'name'=>$decoded->data[$j]->name,
							'picture'=>$decoded->data[$j]->picture->data->url
							));
							break;
						}
					}
				}


			
			

			if($myview==1)
			{
					$service_url = 'https://graph.facebook.com/v2.4/me?access_token='.$accessToken.'&fields=name,picture';

					//make the api call and store the response
					$curl = curl_init($service_url);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					$curl_response = curl_exec($curl);
					
					//if the api call is failed
					if ($curl_response === false) {
					    //$info = curl_getinfo($curl);
					    curl_close($curl);
					    //die('error occured during curl exec. Additioanl info: ' . var_export($info));
					    echo json_encode(array('error'=>'unable to get information from facebook'));
					    exit;

					}
					curl_close($curl);
					$decoded = json_decode($curl_response);
					
					//if the api call is success but error from facebook
					if (isset($decoded->error)) {
						//echo 'error';
					    //die('error occured: ' . $decoded->response->errormessage);
					    echo($curl_response);
					    exit;
					    }

					array_push($output,array(
						'name'=>$decoded->name,
						'picture'=>$decoded->picture->data->url));

					echo json_encode(array('output'=>$output));
			}
					
			else
			{
				echo json_encode(array('output'=>$output));
			}
			
		}

		else
		{
			show_404();
		}
	}


	
	
}


*/