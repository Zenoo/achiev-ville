<?php
	include_once('session.php');
	include_once('../uploaded/dBug.php');

	if(isset($_GET['d'])) session_unset();
	
	if(!isset($_SESSION['uid']) || $_SESSION['uid'] === null || !is_numeric($_SESSION['uid'])){
		header("Location: " . twin_auth_href());
		exit();
	}

    $PARAM_hote='XXXXXXXXX';
	$PARAM_nom_bd='XXXXXXXXXXXX';
	$PARAM_utilisateur='XXXXXXXXXX'; 
	$PARAM_mdp='XXXXXXXXXXXXXX';
	try
	{
		$connexion = new PDO('mysql:host='.$PARAM_hote.';
		dbname='.$PARAM_nom_bd, $PARAM_utilisateur, $PARAM_mdp);
		$connexion->exec("set names utf8");
	}
	catch(Exception $e)
	{
		$error_msg = "Error " . $e->getCode() . " connecting to the database:<br /><em>" . $e->getMessage() . "</em>";
		echo $error_msg;
		return false;
	}


    //User checker
    $sql='SELECT * FROM user WHERE twinId = ' . $_SESSION['uid'];
	$prep=$connexion->prepare($sql);
	$prep->execute();
	$userInfo=$prep->fetchAll(PDO::FETCH_ASSOC);

    //New user
    if(count($userInfo) == 0){
        
        $sql='INSERT INTO user(twinId,name,avatar,locale,oldNames) 
                    VALUES(
                        ' . $_SESSION['uid'] . ',
                        "' . $_SESSION['name'] . '",
                        "' . $_SESSION['avatar'] . '",
                        "' . $_SESSION['locale'] . '",
                        "' . $_SESSION['oldNames'] . '"
                    )';
        $prep=$connexion->prepare($sql);
        $prep->execute();
        
    }
    //Known user
    else{
        //Update user info
        if($_SESSION['avatar'] != $userInfo[0]['avatar']){
            $sql='UPDATE user SET avatar = "' . $_SESSION['avatar'] . '" WHERE twinId =' . $_SESSION['uid'];
            $prep=$connexion->prepare($sql);
            $prep->execute();
        }
        
        if($_SESSION['name'] != $userInfo[0]['name']){
            $sql='UPDATE user SET avatar = "' . $_SESSION['avatar'] . '", oldNames = "' . $_SESSION['oldNames'] . '" WHERE twinId =' . $_SESSION['uid'];
            $prep=$connexion->prepare($sql);
            $prep->execute();
        }
    }

    //Secured user info
    $sql='SELECT * FROM user WHERE twinId = ' . $_SESSION['uid'];
    $prep=$connexion->prepare($sql);
    $prep->execute();
    $userInfo=$prep->fetchAll(PDO::FETCH_ASSOC);

    //City checker
    $sql='SELECT * FROM city WHERE id = ' . $_SESSION['mapId'];
	$prep=$connexion->prepare($sql);
	$prep->execute();
	$cityInfo=$prep->fetchAll(PDO::FETCH_ASSOC);

    //New city
    if(count($cityInfo) == 0){
        
        $sql='INSERT INTO
            city(id,season,name,isPande,days,wid,hei,x,y,isChaos,isDevastated,conspiracy,def,well,isDoorOpen) 
                    VALUES(
                        ' . $_SESSION['hordesMap']->id . ',
                        ' . $_SESSION['hordesMap']->season . ',
                        "' . $_SESSION['hordesMap']->city->name . '",
                        ' . (($_SESSION['hordesMap']->city->hard) ? 1 : 0) . ',
                        ' . $_SESSION['hordesMap']->days . ',
                        ' . $_SESSION['hordesMap']->wid . ',
                        ' . $_SESSION['hordesMap']->hei . ',
                        ' . $_SESSION['hordesMap']->city->x . ',
                        ' . $_SESSION['hordesMap']->city->y . ',
                        ' . (($_SESSION['hordesMap']->city->chaos) ? 1 : 0) . ',
                        ' . (($_SESSION['hordesMap']->city->devast) ? 1 : 0) . ',
                        ' . (($_SESSION['hordesMap']->conspiracy) ? 1 : 0) . ',
                        ' . $_SESSION['hordesMap']->city->defense->total . ',
                        ' . $_SESSION['hordesMap']->city->water . ',
                        ' . $_SESSION['hordesMap']->city->door . '
                    )';
        $prep=$connexion->prepare($sql);
        $prep->execute();
        
        
        if(!$_SESSION['out']){
            foreach($_SESSION['hordesMap']->city->bank as $item){
                $sql='INSERT INTO
                    bank(cityId,itemId,itemCount,itemMax,isItemBroken) 
                            VALUES(
                                ' . $_SESSION['mapId'] . ',
                                ' . $item->id . ',
                                ' . $item->count . ',
                                ' . $item->count . ',
                                ' . (($item->broken) ? 1 : 0) . '
                            )';
                $prep=$connexion->prepare($sql);
                $prep->execute();
            }
        }
        
    }
    //Known city
    else{
        
        //Update city info
        
        function get($path, $object) {
            $pathEx = explode('->', $path);
            $temp = &$object;

            foreach($pathEx as $var) {
                $temp =& $temp->$var;
            }
            return $temp;
        }

        function cityUpdater($name,$now,$db){
            global $connexion;
            for($i=0;$i<(count($now));$i++){
                $current = get($now[$i],$_SESSION['hordesMap']);
                if($current != $db[$name[$i]]){
                    $sql="UPDATE city SET " . $name[$i] . " = " . $current . " WHERE cityId = " . $_SESSION['mapId'];
                    $prep=$connexion->prepare($sql);
                    $prep->execute();
                }
            }
        }
        
        $columnsToUpdate=array('days','isChaos','isDevastated','conspiracy','def','well','isDoorOpen');
        $columnsNow=array(
                            "days",
                            "city->chaos",
                            "city->devast",
                            "conspiracy",
                            "city->defense->total",
                            "city->water",
                            "city->door" 
                        );
        
        cityUpdater($columnsToUpdate,$columnsNow,$cityInfo[0]);
        
        if(!$_SESSION['out']){
            
            function searchInBank($bank,$itemId,$broken){
                $res = null;
                $isB = (($broken) ? 1 : 0);
                foreach($bank as $item) {
                    if ($itemId == $item['itemId'] && $isB == $item['isItemBroken']) {
                        $res = $item;
                        break;
                    }
                }
                return $res;
            }
        
            $sql='SELECT * FROM bank WHERE cityId = ' . $_SESSION['mapId'];
            $prep=$connexion->prepare($sql);
            $prep->execute();
            $bankInfo=$prep->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($_SESSION['hordesMap']->city->bank as $item){
                $itemFound = searchInBank($bankInfo,$item->id,$item->broken);
                if($itemFound === NULL){
                    $sql='INSERT INTO
                        bank(cityId,itemId,itemCount,itemMax,isItemBroken) 
                                VALUES(
                                    ' . $_SESSION['mapId'] . ',
                                    ' . $item->id . ',
                                    ' . $item->count . ',
                                    ' . $item->count . ',
                                    ' . (($item->broken) ? 1 : 0) . '
                                )';
                    $prep=$connexion->prepare($sql);
                    $prep->execute();
                }
                else{
                    $sql="UPDATE bank SET itemCount = " . $item->count . ", itemMax = " . max($item->count,$itemFound['itemMax']) . " WHERE cityId = " . $_SESSION['mapId'] . " AND itemId = " . $item->id . " AND isItemBroken = " . (($item->broken) ? 1 : 0);
                    $prep=$connexion->prepare($sql);
                    $prep->execute();
                }
            }
        }
        
    }

    //Secure city info
    $sql='SELECT * FROM city WHERE id = ' . $_SESSION['mapId'];
    $prep=$connexion->prepare($sql);
    $prep->execute();
    $cityInfo=$prep->fetchAll(PDO::FETCH_ASSOC);

    var_dump($_SESSION);

?>
