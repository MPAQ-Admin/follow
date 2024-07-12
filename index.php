<HTML>
	<HEAD>
		<TITLE>The Follow Back</TITLE>
	</HEAD>
	<BODY>
<?PHP
ini_set('display_errors',1);ini_set('display_startup_errors',1);error_reporting(E_ALL);
include('.config.php');
$user_id=$D=NULL;
if (!isset($_POST['D'])){
	echo "<CENTER>Welcome to &#34;The Follow Back&#34; List<BR>
		When you enter your full address below, we will try to get all public information about who you follow and those who follow you.<BR>
		For this to allow you to follow back, you must be logged into your account in a web browser.<BR>
		The color codes: <FONT COLOR='green'>Green: Mutual</FONT>, <FONT COLOR='red'>Red: Not Mutual</FONT>, <FONT COLOR='black'>Black: Local</FONT><BR><BR>
		<form action='/follow/".$D."' method='post'>
			<input
				type='url'
				name='D'
				id='D'
				placeholder='https://beamship.mpaq.org/@admin'
				size=50
				maxlength=50
				autofocus
				required
			/>
			<button type='submit' name='request' value='".$D."'>Request</button>
		</form>
		</CENTER>
";
exit;
}else{
	$full=$_POST['D'];
	$string=explode("@",$full);
	$username=$string[1];
	$instance=$string[0];
	$userid=$instance."api/v1/accounts/lookup?acct=".$username;
	$json=@file_get_contents($userid);
	$user_data=json_decode($json);
	if (isset($user_data)){
		$user_id=$user_data->id;
		$followers_count=$user_data->followers_count;
		$following_count=$user_data->following_count;
	}else{
		echo "Unable to retrieve data for ".$_POST['D'];
		exit;
	}
}
if (!isset($user_id)){echo "Unknown System Error";exit;}
$con=mysqli_connect($db['host'],$db['user'],$db['passw'],$db['database']);
$con->query("DROP TABLE IF EXISTS ".$user_id."_following;");
$con->query("DROP TABLE IF EXISTS ".$user_id."_followers;");
$following=$instance."api/v1/accounts/$user_id/following";
$followers=$instance."api/v1/accounts/$user_id/followers";
$f0_count=$f1_count=0;
echo "Looking for ".$full." <A HREF='".$userid."' TARGET='_blank'>".$user_id."</A> 
	<A HREF='".$following."' TARGET='_blank'>following</A> 
	<A HREF='".$followers."' TARGET='_blank'>followers</A><BR>
<FONT COLOR='green'>Green: Mutual</FONT>, <FONT COLOR='red'>Red: Not Mutual</FONT>, <FONT COLOR='black'>Black: Local</FONT><BR>";
$output=shell_exec($install_path.'list.sh '.$username.' '.$instance.' 2>&1');
if (isset($output)){echo $output."<BR><BR>";}
echo "<B>followers?</B> ".$followers_count."<BR>";
$sql="SELECT * FROM `".$user_id."_followers` ORDER BY `name` ASC;";
$res= $con->query($sql);
while ($row = $res->fetch_assoc()){
	$remote_user=$row['name'];$f0_count++;
	$s="SELECT * FROM `".$user_id."_following` WHERE `name` LIKE '".$remote_user."';"; 
	$r=$con->query($s);
	$t = $r->fetch_assoc();
	if (isset($row['name'])){
		$name=explode('@',$row['name']);
		if (isset($name[1])){
			$remote="https://".$name[1];
			if (!isset($t['name'])){
				echo "<B><A HREF='".$instance."@".$name[0]."@".$name[1]."' TARGET='_blank' style='background-color:red;color: white;'>".$name[0]."</A></B> ";
			}else{
				echo "<A HREF='".$instance."@".$name[0]."@".$name[1]."' TARGET='_blank' style='background-color:green;color: white;'>".$name[0]."</A> ";
			}
		}else{
			echo "<I><A HREF='".$instance."@".$row['name']."' TARGET='_blank' style='background-color:black;color: white;'>".$row['name']."</A></I> ";
		}
	}
}
echo "<BR><BR><B>following?</B> ".$following_count."<BR>";
$sql="SELECT * FROM `".$user_id."_following` ORDER BY `name` ASC;";
$res= $con->query($sql);
while ($row = $res->fetch_assoc()){
	$remote_user=$row['name'];$f1_count++;
	$s="SELECT * FROM `".$user_id."_followers` WHERE `name` LIKE '".$remote_user."';"; 
	$r=$con->query($s);
	$t = $r->fetch_assoc();
	if (isset($row['name'])){
		$name=explode('@',$row['name']);
		if (isset($name[1])){
			$remote="https://".$name[1];
			if (!isset($t['name'])){
				echo "<B><A HREF='".$instance."@".$name[0]."@".$name[1]."' TARGET='_blank' style='background-color:red;color: white;'>".$name[0]."</A></B> ";
			}
			if (isset($t['name'])){
				echo "<A HREF='".$instance."@".$name[0]."@".$name[1]."' TARGET='_blank' style='background-color:green;color: white;'>".$name[0]."</A> ";
			}
		}else{
			echo "<I><A HREF='".$instance."@".$row['name']."' TARGET='_blank' style='background-color:black;color: white;'>".$row['name']."</A></I> ";
		}
	}
}
$con->close();
if ($f0_count<>$following_count){echo "<BR><B>f0_count & following_count are not adding up, correctly ".$f0_count." <> ".$following_count."</B>";}
if ($f1_count<>$following_count){echo "<BR><BR><B>f1_count & following_count are not adding up, correctly ".$f1_count." <> ".$following_count."</B>";}
if ($f0_count<>$f1_count){echo "<BR><BR><B>f0_count & f1_count are not adding up, correctly ".$f0_count." <> ".$f1_count."</B>";}
if ($followers_count>$following_count){echo "<BR><BR><B>Missing following ".$followers_count." < ".$following_count."</B>";}
if ($followers_count<$following_count){echo "<BR><BR><B>Missing followers ".$followers_count." > ".$following_count."</B>";}
?>
