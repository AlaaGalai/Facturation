<?php
class travail extends calendar
{
	function travail()
	{
		parent::__construct(false);
		if(!$_POST["jourdateLicenciement"]) $_POST["jourdateLicenciement"] = $this->univ_strftime("%d", time());
		if(!$_POST["moisdateLicenciement"]) $_POST["moisdateLicenciement"] = $this->univ_strftime("%m", time());
		if(!$_POST["anneedateLicenciement"]) $_POST["anneedateLicenciement"] = $this->univ_strftime("%Y", time());
/*		if(!$_POST["nbMaladies"]) $_POST["nbMaladies"] = 1;
		if(!$_POST["nbGrossesses"]) $_POST["nbGrossesses"] = 1;
		if(!$_POST["nbServices"]) $_POST["nbServices"] = 1;*/
		
		if($this->getCookie("locale")) setlocale(LC_TIME, $this->getCookie("locale_cookie"));
		else setlocale(LC_TIME, "fr_FR");

	}
}	
?>
