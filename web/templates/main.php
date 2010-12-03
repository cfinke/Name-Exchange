<? include "templates/header.php"; ?>
<h1>Gift Exchange</h1>
<p>Enter the names and email addresses of the people involved, one per line.</p>
<p>If you want to have multiple groups where each person cannot draw the name of someone in their own group, put a blank line between the groups of names.</p>
<form method="post" action="">
	<? if ($error) { ?>
		<p class="error"><?=$error?></p>
	<? } ?>
	<textarea name="names" rows="30" cols="100"><?=htmlspecialchars($names)?></textarea>
	<input type="submit" value="Continue" />
</form>
<? include "templates/footer.php"; ?>