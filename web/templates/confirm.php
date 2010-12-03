<? include "templates/header.php"; ?>
<p>When you continue, Name Exchange will email a random name to each of the following people.</p>
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
<form method="post" action="">
	<input type="hidden" name="households" value="<?=base64_encode(serialize($households))?>" />
	<input type="submit" value="Ok, Continue" />
</form>
<form method="post" action="">
	<input type="hidden" name="initial_names" value="<?=htmlspecialchars($names)?>" />
	<input type="submit" value="Wait, I want to make changes." />
</form>

<? include "templates/footer.php"; ?>