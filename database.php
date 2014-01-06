<?php
try
{
	// maak databaseconnectie met PDO
	$db = new PDO('mysql:host=localhost;dbname=furry-ninja', 'furry-ninja', 'rcz5eqRPaazt5wMN');
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
	die('Er kan geen verbinding worden gemaakt met de database :(');
}
