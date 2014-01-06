<?php
require_once 'database.php';

/* TODO
 *
 * - labels op de X-as
 * - styling van de ul's en li's
 * - comments
 */


if(isset($_GET['ajaj']))
{
	header('Content-Type: application/json');

	$plaats_id = filter_input(INPUT_GET, 'plaats', FILTER_VALIDATE_INT);
	$postcode_id = filter_input(INPUT_GET, 'postcode', FILTER_VALIDATE_INT);
	if($plaats_id)
	{
		$statement = $db->prepare('SELECT postcode_id, postcode_num FROM postcode WHERE plaats_id = :plaats_id');
		$statement->bindParam(':plaats_id', $plaats_id, PDO::PARAM_INT);
		$statement->execute();
		echo json_encode($statement->fetchAll(PDO::FETCH_ASSOC));
	}
	elseif($postcode_id)
	{
		$statement = $db->prepare('SELECT * FROM aantal WHERE postcode_id = :postcode_id ORDER BY geslacht_id, leeftijd_id');
		$statement->bindParam(':postcode_id', $postcode_id, PDO::PARAM_INT);
		$statement->execute();
		echo json_encode($statement->fetchAll(PDO::FETCH_ASSOC));
	}

	die;

}


?>
<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Furry Ninja!</title>
		<style>
			body {
				font-family: sans-serif;
			}
			span {
				cursor: pointer;
			}
		</style>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//www.google.com/jsapi"></script>
		<script>
			google.load("visualization", "1", {packages:["corechart"]});

			plaatsen = new Array();
			postcodes = new Array();

			function expand_plaats(plaats_id)
			{
				if(!plaatsen[plaats_id])
				{
					$.get('/', {ajaj: null, plaats: plaats_id}, function(data, status)
					{
						var ul = $('<ul/>');

						for(var i in data)
						{
							ul.append($('<li/>', {
								class: 'postcode',
								id: 'postcode_'+data[i].postcode_id,
							}).append($('<span/>', {
								text: data[i].postcode_num
							}).click(data[i], function(event)
							{
								if(!postcodes[event.data.postcode_id])
								{
									$.get('/', {ajaj: null, postcode: event.data.postcode_id}, function(data, status)
									{
										var geocode_string = event.data.postcode_num+'%2CThe%20Netherlands';

										var data_string = 't:';

										for(var i in data)
										{
											data_string += data[i].aantal;
											if(i == 19)
											{
												data_string += '|';
											}
											else if(i < 39)
											{
												data_string += ',';
											}
										}

										var div = $('<div/>');
										div.append($('<a/>', {
											href: 'https://maps.google.nl/maps?q='+geocode_string,
											target: '_blank'
										}).append($('<img/>', {
											src: 'http://maps.googleapis.com/maps/api/staticmap?center='+geocode_string+'&zoom=14&size=300x300&maptype=roadmap&sensor=false',
											width: 300,
											height: 300,
											alt: event.data.postcode_num
										})).append($('<img/>', {
											src: 'https://chart.googleapis.com/chart?cht=lc&chco=0000FF,FF0000&chs=300x300&chd='+data_string+'&chxt=x,y&chds=a',
											width: 300,
											height: 300,
											alt: event.data.postcode_num
										}).css('margin-left', '2em')));

										div.hide();
										$('#postcode_'+event.data.postcode_id).append(div)
										div.slideToggle();

										postcodes[event.data.postcode_id] = true;
									});
								}
								else
								{
									$('#postcode_'+event.data.postcode_id+' > div').slideToggle();
								}
							})));
						}

						ul.hide();
						$('#plaats_'+plaats_id).append(ul);
						ul.slideToggle();

						plaatsen[plaats_id] = true;
					});
				}
				else
				{
					$('#plaats_'+plaats_id+' > ul').slideToggle();
				}
			}
		</script>
	</head>
	<body>
		<?php
			$statement = $db->query('SELECT plaats_id, plaats_naam FROM plaats ORDER BY plaats_naam');
			echo '<ul>';
			while($row = $statement->fetch(PDO::FETCH_ASSOC))
			{
				echo '<li id="plaats_'.$row['plaats_id'].'"><span onclick="expand_plaats('.$row['plaats_id'].')">'.$row['plaats_naam'].'</span></li>';
			}
			echo '</ul>';
		?>
	</body>
</html>
