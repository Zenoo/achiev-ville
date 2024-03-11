<?php







    $PARAM_hote='XXXXXXXXX';

	$PARAM_nom_bd='XXXXXXXXX';

	$PARAM_utilisateur='XXXXXXXXXXXX'; 

	$PARAM_mdp='XXXXXXXXXXXXX';

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

    

    if (file_exists('c_building.xml')) {

        $xml = simplexml_load_file('c_building.xml');

        

        foreach($xml[0]->c_building->building as $build){

            $sql='INSERT INTO construction ( id, name, rarity, def, hasUpgrades, isBreakable, isTemporary, maxLife ) 

                VALUES(

                    ' . $build['id'] . ',

                    "' . $build['name'] . '",

                    "",

                    ' . $build['def'] . ',

                    0,

                    0,

                    ' . $build['temporary'] . ',

                    0)';

            print_r($sql); echo '<br />';

            $prep=$connexion->prepare($sql);

            $prep->execute();

        }



    } else {

        exit('Echec lors de l\'ouverture du fichier.');

    }



    









?>