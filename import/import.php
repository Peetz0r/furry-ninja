<?php
die('Dit script duurt een half uur ofzo, dus ik heb er eventjes een die(); boven gezet.');

require_once '../database.php';

// truncate alle tables
$tables = Array('aantal', 'geslacht', 'leeftijd', 'plaats', 'postcode');
foreach($tables as $table)
{
	$statement = $db->query('TRUNCATE TABLE '.$table);
}

// haal alle statement-instanties buiten de loops
$leeftijd_statement      = $db->prepare('INSERT INTO leeftijd (leeftijd_id, leeftijd_desc) VALUES (:id, :desc)');
$plaats_select_statement = $db->prepare('SELECT plaats_id FROM plaats WHERE plaats_naam = :plaats');
$plaats_insert_statement = $db->prepare('INSERT INTO plaats (plaats_naam) VALUES (:plaats)');
$postcode_statement      = $db->prepare('INSERT INTO postcode (postcode_num, plaats_id) VALUES (:postcode, :plaats_id)');
$aantal_statement        = $db->prepare('INSERT INTO aantal (geslacht_id, leeftijd_id, postcode_id, aantal) VALUES (:geslacht, :leeftijd, :postcode, :aantal)');


// insert de geslachten
$statement = $db->query('INSERT INTO geslacht (geslacht_id, geslacht_desc) VALUES (0, \'Mannen\'), (1, \'Vrouwen\')');

// en de leeftijden
$db->beginTransaction();
for($id=0; $id<20; $id++)
{
	$desc = ($id*5).' - '.(($id+1)*5).'';
	if($id==19) $desc = '95+';

	$leeftijd_statement->bindParam(':id', $id, PDO::PARAM_INT);
	$leeftijd_statement->bindParam(':desc', $desc, PDO::PARAM_STR);
	$leeftijd_statement->execute();
}
$db->commit();

// open het csv-bestand van het CBS om te lezen
// 1 januari 2013: http://statline.cbs.nl/StatWeb/selection/default.aspx?VW=T&DM=SLNL&PA=82245NED&D1=22-41%2c43-62&D2=a&HDR=T&STB=G1
$filename = 'Bevolking__postcode__050114132044.csv';
$file = fopen($filename, 'r');

// lees regel voor regel, en parse csv-data
while($fields = fgetcsv($file, 0, ';', '"'))
{
	// neem de eerste 4 tekens van het eerste veld, en kijk of dat ook echt cijfers zijn
	$postcode = substr($fields[0], 0, 4);

	// ctype_digit is stricter dan is_numeric, dus beter voor ons
	if(ctype_digit($postcode))
	{
		// de tekst vanaf het 6e teken is de plaatsnaam, die willen we ook hebben
		$plaats = substr(array_shift($fields), 6);

		// zoek het ID van de plaats
		$plaats_select_statement->bindParam(':plaats', $plaats, PDO::PARAM_STR);
		$plaats_select_statement->execute();
		$plaats_id = $plaats_select_statement->fetchColumn();

		if($plaats_id === false)
		{
			// als we deze niet kunnen vinden voegen we hem toe (415)
			$plaats_insert_statement->bindParam(':plaats', $plaats, PDO::PARAM_STR);
			$plaats_insert_statement->execute();
			$plaats_id = $db->lastInsertId();
		}

		// insert de postcodes (4033)
		$postcode_statement->bindParam(':postcode',  $postcode,  PDO::PARAM_INT);
		$postcode_statement->bindParam(':plaats_id', $plaats_id, PDO::PARAM_INT);
		$postcode_statement->execute();
		$postcode_id = $db->lastInsertId();

		// de overgebleven 2x20 velden splitsen in mannen en vrouwen
		$fields = array_chunk($fields, 20);

		// loop over de geslachten
		$db->beginTransaction();
		foreach($fields as $geslacht=>$leeftijden)
		{
			// loop over de leeftijden
			foreach($leeftijden as $leeftijd=>$aantal)
			{
				// insert de aantallen (160.000)
				$aantal_statement->bindParam(':geslacht', $geslacht,    PDO::PARAM_INT);
				$aantal_statement->bindParam(':leeftijd', $leeftijd,    PDO::PARAM_INT);
				$aantal_statement->bindParam(':postcode', $postcode_id, PDO::PARAM_INT);
				$aantal_statement->bindParam(':aantal',   $aantal,      PDO::PARAM_INT);
				$aantal_statement->execute();

			}
		}
		$db->commit();
	}
}
