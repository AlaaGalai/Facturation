<?php
//Class PDF
//Written by Olivier Subilia



class PDF extends FPDF
{
	function __construct()
	{
		for($path='.'; !is_file("{$path}/root.php"); $path= "../$path"){};

		define('FPDF_FONTPATH',$path.'/externe/fpdf_fonts');
		parent::__construct();
		$this->AliasNbPages();
	}


	function footer()
	{
		$this->setY(-15);
		$this->setFont("Arial", '', "10");
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
		
	}
	
	function NbLines($w, $txt, $h=False, $check=False)
	{
		//Calcule le nombre de lignes qu'occupe un MultiCell de largeur w
		//Si la hauteur h est fournie, renvoie la hauteur de ces lignes
		$cw=&$this->CurrentFont['cw'];
		if($w==0)
			$w=$this->w-$this->rMargin-$this->x;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		if($nb>0 and $s[$nb-1]=="\n")
			$nb--;
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$nl=1;
		while($i<$nb)
		{
			$c=$s[$i];
			if($c=="\n")
			{
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
				continue;
			}
			if($c==' ')
				$sep=$i;
			$l+=$cw[$c];
			if($l>$wmax)
			{
				if($sep==-1)
				{
					if($i==$j)
						$i++;
				}
				else
					$i=$sep+1;
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
			}
			else
				$i++;
		}
		$pB = $this->getY() + ($h * $nl);
		if ($check === 4) return $pB;
		if ($h and $check)
		{
			if ($pB > $this->PageBreakTrigger) return "1";
			else return "0";
		}
		elseif($h) return $nl * $h;
		else return $nl;
	}


}
