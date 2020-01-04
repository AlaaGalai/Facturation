var hasDeleted = false;
var allreadySubmitted = false;
var wDlSize = 500;
var hDlSize = 500;
var wRdvSize = 550;
var hRdvSize = 700;
function doNothing()
{
}

function getDate(id)
{
	d = '';
	for ( el in a = ['annee', 'mois', 'jour'])
	{
// 		console.log(a[el]);
		i = a[el] + id;
		if(d != '') d += '-';
		if(document.getElementById(i)) e = document.getElementById(i).value;
		else e='00';
		d += e;
	}
	return (d);
}

function getTime(id)
{
	d = '';
	for ( el in a = ['heure', 'minute'])
	{
// 		console.log(a[el]);
		i = a[el] + id;
		if(d != '') d += ':';
		if(document.getElementById(i)) e = document.getElementById(i).value;
		else e='00';
		d += e;
	}
	return (d);
}

function copyDl(id)
{
	openDl(id, true);
}

function openDl(id, copy, nouveau, date, personne, libelle="", dossier="")
{
	loc = 'modifier_delai.php?id=' + id;
	if (copy) loc += '&copy=on';
	if (nouveau)
	{
		if(libelle) libelle = '&libelle=' + libelle;
		else libelle = '';
		if(dossier) dossier = '&dossier=' + dossier;
		else dossier = '';
		loc = 'modifier_delai.php?nouveau=on&date_cours=' + date + '&dl_pour=' + personne + libelle + dossier;
	}
	window.open(loc,'modifier','width=' + wDlSize +',height=' + hDlSize + ',toolbar=no,directories=no,menubar=no=no,location=no,status=no');
}

function copyRdv(id, link)
{
	openRdv(id, link);
}

function openRdv(id, copy, nouveau, date, heure, personne, type, libelle="", dossier="")
{
	loc = 'modifier_rdv.php?id=' + id;
	if (copy) loc += '&copy=on';
	if (copy > 0) loc += '&linkid=' + copy;
	if (nouveau)
	{
		if(type) type = '&type=' + type;
		else type = '';
		if(libelle) libelle = '&libelle=' + libelle;
		else libelle = '';
		if(dossier) dossier = '&dossier=' + dossier;
		else dossier = '';
		loc = 'modifier_rdv.php?nouveau=on&date_cours=' + date + '&heure_debut=' + heure + '&rdv_pour=' + personne + type + libelle + dossier;
	}
	window.open(loc,'modifier','width=' + wRdvSize +',height=' + hRdvSize + ',toolbar=no,directories=no,menubar=no=no,location=no,status=no');
}

function newRdv(date, heure, personne, type, libelle="", dossier="")
{
	openRdv(id='', copy=false, nouveau=true, date, heure, personne, type, encodeURIComponent(libelle), dossier);
}

function newDl(date, personne, libelle="", dossier="")
{
	openDl(id='', copy=false, nouveau=true, date, personne, encodeURIComponent(libelle), dossier);
}

function reloadFrame()
{
	var specCN = '';
	jour_debut = (document.getElementById('jour_debut')) ? document.getElementById('jour_debut').value:false;
	jour_fin = (document.getElementById('jour_fin')) ? document.getElementById('jour_fin').value: false;
	mois_debut = (document.getElementById('mois_debut')) ? document.getElementById('mois_debut').value:false;
	mois_fin = (document.getElementById('mois_fin')) ? document.getElementById('mois_fin').value: false;
	annee_debut = (document.getElementById('annee_debut')) ? document.getElementById('annee_debut').value:false;
	annee_fin = (document.getElementById('annee_fin')) ? document.getElementById('annee_fin').value: false;
	if(((jour_debut == jour_fin && mois_debut == mois_fin && annee_debut == annee_fin)|| (!jour_debut && !mois_debut && !annee_debut)) && !hasDeleted)
	{
		if(!jour_debut) jour_debut = jour_fin;
		if(!mois_debut) mois_debut = mois_fin;
		if(!annee_debut) annee_debut = annee_fin;
		frameId = 'framejour_' + annee_debut + '-' + mois_debut + '-' + jour_debut;
		if(window.opener.document.getElementById(frameId))
		{
			calDiv = window.opener.document.getElementById(frameId);
			calType = 'jourReload';
			specCN = calDiv.className;
		}
	}
	else if(window.opener.document.getElementById('framesemaine'))
	{
		calDiv = window.opener.document.getElementById('framesemaine');
		calType = 'semaine';
	}
	else if(window.opener.document.getElementById('framemois'))
	{
		calDiv = window.opener.document.getElementById('framemois');
		calType = 'mois';
	}
	if(typeof(calDiv) != "undefined")
	{
		dateCours = calType == 'jourReload'?annee_debut + '-' + mois_debut + '-' + jour_debut:window.opener.dateReload;
		oldClass = calDiv.className;
		calDiv.className = 'attention_bg';
		XHR = new XHRConnection();
		data='';
		if(specCN) XHR.appendData('specCN', specCN);
		XHR.appendData('agendaSolo', calType);
		XHR.appendData('date_cours', dateCours);
		XHR.appendData('persReload', window.opener.persReload);
		XHR.sendAndLoad( './agenda.php', 'POST', reloadDiv);
	}
	else
	{
		o = window.opener;
		sx = o.scrollX;
		sy = o.scrollY;
// 		alert('x: ' + sx + ' ; y: ' + sy);
// 		o.location.href = o.location.href;
		o.document.getElementById("nextScrollX").value = sx;
		o.document.getElementById("nextScrollY").value = sy;
 		if (window.opener.document.getElementById('self_reload'))/*problème avec android TODO*/ window.opener.document.getElementById('self_reload').submit();
// 		o.window.scrollBy(sx, sy);
		self.close();
	}
}

var reloadDiv = function (obj) 
{ 
	calDiv.innerHTML = obj.responseText;
	calDiv.className = oldClass;
	self.close();
} 

function verifyDate(errorMessage1,errorMessage2)
{
	minuteDebut = (document.getElementById('minute_debut')) ? document.getElementById('minute_debut').value: '0';
	heureDebut = (document.getElementById('heure_debut')) ? document.getElementById('heure_debut').value: '0';
	jourDebut = document.getElementById('jour_debut').value;
	moisDebut = document.getElementById('mois_debut').value;
	anneeDebut = document.getElementById('annee_debut').value;
	minuteFin = (document.getElementById('minute_fin')) ? document.getElementById('minute_fin').value: '0';
	heureFin = (document.getElementById('heure_fin')) ? document.getElementById('heure_fin').value: '0';
	jourFin = document.getElementById('jour_fin').value;
	moisFin = document.getElementById('mois_fin').value;
	anneeFin = document.getElementById('annee_fin').value;
	jourRepet = (document.getElementById('jour_repete_fin')) ? document.getElementById('jour_repete_fin').value: '1';
	moisRepet = (document.getElementById('mois_repete_fin')) ? document.getElementById('mois_repete_fin').value: '1';
	anneeRepet = (document.getElementById('annee_repete_fin')) ? document.getElementById('annee_repete_fin').value: '1900';
	
	var repet = '';
	if (document.getElementById('repete'))
	{
		//selection=document.getElementById('repete').selectedIndex;
		//repet=document.getElementById('repete').options[selection].value;
		repet=document.getElementById('repete').checked;
	}

	moisDebut --; //les mois vont de 0 à 11 en Javascript;
	moisFin --; //les mois vont de 0 à 11 en Javascript;
	moisRepet --; //les mois vont de 0 à 11 en Javascript;
	
	var dateDebut   = new Date(anneeDebut,moisDebut,jourDebut,heureDebut,minuteDebut);
	var dateFin     = new Date(anneeFin,moisFin,jourFin,heureFin,minuteFin);
	var dateRepet   = new Date(anneeRepet,moisRepet,jourRepet,heureFin,minuteFin);
	
	timeDebut = dateDebut.getTime();
	timeFin = dateFin.getTime();
	timeRepet = dateRepet.getTime();
	
	total = ' : Du ' + jourDebut + '.' + moisDebut + '.' + anneeDebut + ' a ' + heureDebut + ':' + minuteDebut + ' au ' + jourFin + '.' + moisFin + '.' + anneeFin + ' a ' + heureFin + ':' + minuteFin + ' soit du ' + dateDebut + ' au ' + dateFin + ' ou du ' + timeDebut + ' au ' + timeFin;
	
	var repetOk=false;
	var suiteOk=false;
	var errorMessage='';
	
	if(repet == '' || timeRepet > timeFin || (timeRepet <1)) repetOk=true;
	
	if(timeFin > timeDebut)
	{
		suiteOk=true;
	}
	
	//Correct contentEditable docs
	if(document.getElementById('eDiv_libelle')) document.getElementById('libelle').value = document.getElementById('eDiv_libelle').innerHTML;

	if(repetOk && suiteOk)
	{
		document.getElementById('modifier').action = 'maj_op.php';
		document.getElementById('modifier').submit();
	}
	
	else
	{
		if(!suiteOk) errorMessage=errorMessage1;
		if(!suiteOk && !repetOk) errorMessage += '\n';
		if(!repetOk) errorMessage += errorMessage2;
		document.getElementById('dateError').innerHTML = errorMessage;
		document.getElementById('dateError').className = 'attention';
	}
	
}

function linkDate()
{
	if(document.getElementById('linked').checked)
	{
		jd = document.getElementById('jour_debut').value;
		md = document.getElementById('mois_debut').value -1; //Attention: les mois commencent par zéro
		ad = document.getElementById('annee_debut').value;
		hd = document.getElementById('heure_debut').value;
		nd = document.getElementById('minute_debut').value; //Attention: nd != md
// 		jf = document.getElementById('jour_fin').value;
// 		mf = document.getElementById('mois_fin').value -1;
// 		af = document.getElementById('annee_fin').value;
		
		jDebutInit = new Date(adi, mdi, jdi, hdi, ndi).getTime()/1000// + new Date(adi, mdi, jdi, hdi, ndi).getTimezoneOffset()*60;
		jFinInit = new Date(afi, mfi, jfi, hfi, nfi).getTime()/1000
		nDebut = new Date(ad, md, jd, hd, nd).getTime()/1000
		difference = nDebut - jDebutInit;
		nFinTS = (jFinInit + difference) * 1000;
		nFin = new Date(nFinTS);
		jf = nFin.getDate();
		mf = nFin.getMonth() + 1; //Attention: les mois commencent par zéro
		af = nFin.getFullYear();
		hf = nFin.getHours();
		nf = nFin.getMinutes();
		if(nf == "0") nf = "00"; 
		document.getElementById('jour_fin').value=jf;
		document.getElementById('mois_fin').value=mf;
		document.getElementById('annee_fin').value=af;
		document.getElementById('heure_fin').value=hf;
		document.getElementById('minute_fin').value=nf;
	}
}

function changeMonth(el, d)
{
	alert(d);
}
