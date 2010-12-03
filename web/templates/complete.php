<? include "templates/header.php"; ?>
<h2>All Done.</h2>
<p>Names have been sent to the following people:</p>
<? $i = 0; foreach ($households as $house) { $i++; ?>
	<? if (count($households) > 1) { ?>
		<h2>Group #<?=$i?></h2>
	<? } ?>
	
	<ul>
		<? foreach ($house as $member) { ?>
			<li><?=htmlspecialchars($member["name"])?><? if ($member["name"] != $member["email"]) { ?> (<?=htmlspecialchars($member["email"])?>)<? } ?></li>
		<? } ?>
	</ul>
<? } ?>
<? include "templates/footer.php"; ?>