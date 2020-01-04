// var liste = new Array();
var encours = "neant";
var itemencours = "neant";
var ancel = "neant";
var actuel="off";
var ouvert="off";
var subitem="neant"
var showactuel = false;
var messageactuel = false;
var x=0;
var y=0;
if(! oDragObj) var oDragObj = null;
var pda = false;
var preventHide = false;
var ecartX=20;
var ecartY=50;
var position=''
var isWaiting = false;
var wNoteSize = 550;
var hNoteSize = 700;



function setPos(souris)
{
	x = (navigator.appName.substring(0,3) == "Net") ? souris.pageX : event.x + document.body.scrollLeft;
	y = (navigator.appName.substring(0,3) == "Net") ? souris.pageY : event.y + document.body.scrollTop;
	//alert(x);
        e = window.event;
	iMousePosX = x; //compatibilité avec la librairie de déplacement
	iMousePosY = y; //compatibilité avec la librairie de déplacement
// 	iMousePosY = 20 * Math.floor(y / 20);
	x+=ecartX;
	y-=ecartY;
// 	document.getElementById("mousepos").innerHTML = iMousePosX + ", " + iMousePosY;
	
	if(messageactuel && showactuel) show(messageactuel, position);

// 	document.getElementById('plagetest').innerHTML = 'En cours : ' + encours;
	
	if (oDragObj != null)
	{
		compx=0;
		compy=0;
		
		oDragObj.style.top = (iMousePosY - iDragObjTopDiff) + "px";
		
//		oDragObj.style.left = (iMousePosX - iDragObjLeftDiff) + "px";
// 		oDragObj.style.left = (20 * actx) + "px";
//  		document.getElementById(plageid).innerHTML = iMousePosY + '/' + iMousePosX + '( soit pour le top: ' + oDragObj.style.top + ')';
	}
}

window.document.onmousemove = setPos;

window.name='principal';


function resize(sizex,sizey)
{
	window.resizeTo(sizex,sizey);
}

function changeDossier(nodossier, secteur)
{
	document.changedossier.nodossier.value = nodossier;
	document.changedossier.secteur.value = secteur;
	document.changedossier.submit();
}

function selectBox(element, id)
{
	if(document.all)
	{
		to_apply=document.all[element];
		id_check=document.all[id];
	}
	if(document.getElementById)
	{
		to_apply=document.getElementById(element);
		id_check=document.getElementById(id);
	}
	id_check.checked=true
	to_apply.className="selected_op";
}

function select_color(element, check)
{
	if(document.all)
	{
		to_apply=document.all[element];
		to_check=document.all[check];
	}
	if(document.getElementById)
	{
		to_apply=document.getElementById(element);
		to_check=document.getElementById(check);
	}
	if(!liste[element]) liste[element] = "";
	if(to_check.checked!=true)
	{
		to_apply.className=liste[element];
	}else{
		if(to_apply.className.indexOf("selected_op") == -1) liste[element]=to_apply.className;
		to_apply.className="selected_op";
	}
}

function show(message, position)
{
	//alert(position);
	//if(position == 'left')
	//{
	//	x -= 2*ecartX;
	//}
	messageactuel=message;
	showactuel=true;
	
	if(document.all)
	{
		box = document.all["popbox"];
	}
	if(document.getElementById)
	{
		box = document.getElementById("popbox");
	}
	if(pda)
	{
		if(box.style.visibility == "visible")
		{
			box.style.visibility= "hidden";
			messageactuel=false;
			showactuel=false;
		}
		else
		{
			box.style.visibility= "visible";
			box.style.zIndex= "10000";
		}
	}
	else
	{
		box.style.visibility= "visible";
		box.style.zIndex= "10000";
	}
	box.innerHTML=message;
	aY = y + 'px';
        aX = x + 'px';
	/*if(position == 'left') box.style.right=aX
	else */box.style.left=aX;
        box.style.top=aY;
	//alert(aX)
}

function shi(np, nple, mp, mple)
{
// 	alert(nple + ' ' + mple); 
	if(np != "") np = npA + ' ' + np;
	if(mp != "") mp = mpA + ' ' + mp;
	if(nple != "" && nple != "0000-00-00")
	{
		nple = new Date(nple);
		nple = nple.toLocaleDateString();
		nple = ' (' + nple + ')';
	}
	else nple = '(?)';
	if(mple != "" && mple != "0000-00-00")
	{
		mple = new Date(mple);
		mple = mple.toLocaleDateString();
		mple = ' (' + mple + ')';
	}
	else mple = '(?)';
	if(np != "" && mp != "") semicolon = " ; ";
	else semicolon = "";
	texte = np + nple + semicolon + mp + mple;
	show(texte);
}

function hide()
{
	box.style.visibility= "hidden";
	messageactuel=false;
	showactuel=false;
}

function show_static(message,id,refererid)
{
	if(document.all)
	{
		box_static = document.all[id];
		box_refererid = document.all[refererid];
	}
	if(document.getElementById)
	{
		box_static = document.getElementById(id);
		box_refererid = document.getElementById(refererid);
	}
	box_static.style.visibility= "visible";
	box_static.innerHTML=message;
}

function hide_static()
{
	box_static.style.visibility= "hidden";
	box_static.innerHTML="";
}

function hidecondi()
{
	if(preventHide)
	{
		preventHide = false;
	}
	else
	{
		//alert(id);
		bodyClicked = 'True';
		if(actuel != "on")
		{
			showmenu(encours,"hide");
			ouvert="off";
			encours = "neant";
		}
		hidesubitem();
	}
}

function hideandselect(val)
{
	color="color:" + val;
	box_refererid.value=val;
	box_refererid.style.backgroundColor=val;
	hide_static();
	hide();
}

function showcondi(nomid, etat)
{
	if(document.all){
		menu = document.all[nomid];
		elencours = document.all[encours];
		elitemencours = document.all[itemencours];
	}
	if(document.getElementById){
		menu = document.getElementById(nomid);
		elencours = document.getElementById(encours);
		elitemencours = document.getElementById(itemencours);
	}
	if (ouvert == 'on' && nomid != encours) showmenu(nomid, etat, 'condi');
}

function showpdamenu()
{
	pdamenu = document.getElementById('pdamenu');
	if(pdamenu.style.visibility == 'hidden' || pdamenu.style.visibility == '') pdamenu.style.visibility = 'visible';
	else pdamenu.style.visibility = 'hidden';
}

function showmenu(nomid, etat, appel)
{
	if(document.all){
		menu = document.all[nomid];
		elencours = document.all[encours];
// 		elitemencours = document.all[itemencours];
	}
	if(document.getElementById){
		menu = document.getElementById(nomid);
		elencours = document.getElementById(encours);
// 		elitemencours = document.getElementById(itemencours); //réservé pour les sous-menus
	}
	if(menu.style.visibility == "hidden" || menu.style.visibility == ""){
		menu.style.visibility= "visible";
		if(nomid != encours) {
			elencours.style.visibility= "hidden";
// 			elitemencours.style.visibility="hidden"; //réservé pour les sous-menus
			ouvert='on';
			encours = nomid;
		}
		pdamenu = document.getElementById('pdamenu');
// 		pdamenu.style.visibility = 'hidden';
	}else{
		menu.style.visibility = "hidden";
		ouvert="off";
		encours='neant';
// 		alert(encours);
	}
/*	encours = nomid;*/
	actuel='on';
	setTimeout("actuel='off'", 1000);
}

function showitem(nomid, etat)
{
	
	if(document.all){
		menu = document.all[nomid];
		elencours = document.all[itemencours];
	}
	
	if(document.getElementById){
		menu = document.getElementById(nomid);
		elencours = document.getElementById(itemencours);
	}
	
	if(menu.style.visibility == "hidden" || menu.style.visibility == ""){
		menu.style.visibility= "visible";
		if(nomid != itemencours) elencours.style.visibility= "hidden";
	}
	
	else menu.style.visibility = "hidden";
	if(etat == "hide") menu.style.visibility = "hidden";
	itemencours = nomid;
}

function hidesubitem()
{
	if(subitem != "neant")
	{
		document.getElementById(subitem).style.visibility = "hidden";
		subitem = "neant";
	}
	
}

function menuover(id)
{
	sm = 'submenu.' + id;
	mn = 'menu' + id;
	showcondi(sm);
	document.getElementById(mn).className='bordurefirst';
	hidesubitem();
}
function menuout(id)
{
	m = 'menu' + id;
	if(pda) document.getElementById(m).className='menu';
	else document.getElementById(m).className='menuinit';
}

function subover(id1, id2)
{
	/*ids = id1.split(',');
	for (el in ids)
	{
		if (el == 0) button = 'button' + ids[el] + '_' + id2;
		else button = ids[el];
		//alert(button);
		document.getElementById(button).className='bordure';
	}
	*/
	button = 'button'   + id1 + '_' + id2;
	line   = 'line'   + id1 + '_' + id2;
	select = 'select'   + id1 + '_' + id2;
	document.getElementById(button).className='bordure';
	document.getElementById(line).className='bordurefirst';
	if(document.getElementById(select)) document.getElementById(select).className='bordurefirst';
	hidesubitem();
}

function subout(id1,id2, classname)
{
	button = 'button' + id1 + '_' + id2;
	line   = 'line'   + id1 + '_' + id2;
	select = 'select'   + id1 + '_' + id2;
	document.getElementById(button).className=classname;
	document.getElementById(line).className=classname;
	if(document.getElementById(select)) document.getElementById(select).className=classname;
}

function pleinEcran(element)
{
// 	window.open("index.php", "etude", "scrollbars");
// 	self.close();
	if (! element) element=document.documentElement;
	return (element.requestFullscreen ||
	element.webkitRequestFullscreen ||
	element.mozRequestFullScreen ||
	element.msRequestFullscreen).call(element);
}

var div = null;

function boucle(execution)
{
	texte = texte + 'toto, ' + compteur;
// 	document.getElementById('headers').innerHTML = texte;
	compteur ++;
	if(wait != false && compteur < 10) setTimeout("boucle();", 1000);
	else eval(execution);
}

function doMenu(id,menuValue)
{
	menuId = 'list'+id;
// 	alert(id);
	toChange=document.getElementById(menuId);
	if(toChange.innerHTML == '') toChange.innerHTML=menuValue;
	else toChange.innerHTML = '';
// 	alert(menuValue);
}

function goGonnect(indexPage)
{
	var datas="";
	if(isWaiting) return; //évite de lancer deux fois la même fonction
	isWaiting=true;
// 	wElement=document.getElementById('connectBox');
// 	doWait(wElement);
	var dataToSend = new Array('start_utilisateur', 'start_pwd');
	XHR = new XHRConnection();
	data = 'callback';
	XHR.appendData(data, 'on');
	XHR.appendData('singleconnect', 'true');
	XHR.appendData('new_check', 'on');
	for (el in dataToSend)
	{
 		nom = dataToSend[el];
 		val = document.getElementById(nom).value;
 		XHR.appendData(nom, val);
 	}
	div = 'connectBox';
	XHR.sendAndLoad(indexPage, 'POST', remplirChamp); 
}

var remplirChamp = function (obj) 
{
	isWaiting = false;
	document.getElementById(div).innerHTML = obj.responseText;
	if(obj.responseText.substr(0, 4) == '<ok>')
	{
		console.log('connected');
		document.getElementById('self_reload').submit();
	}
// 	else console.log(obj.responseText.substr(0, 4));
//  	wait= false;
} 

var remplirValue = function (obj) 
{ 
	document.getElementById(div).value = obj.responseText;
//  	wait= false;
} 

function doWait(wElement)
{
	if(isWaiting)
	{
	
		actVal=wElement.innerHTML;
		actVal += '.';
		wElement.innerHTML=actVal;
		setTimeout("doWait(wElement)", 500);
	}
	else return;
}

var reloadPage = function (obj)
{
	document.getElementById("nextScrollX").value = window.scrollX;
	document.getElementById("nextScrollY").value = window.scrollY;
	document.getElementById("self_reload").submit();
}

function sendData(data, value, page, method, callback, func) 
{ 
	//if(func) alert ('func');
	div = callback;
	if (typeof(loading) == 'undefined') loading = "loading";
	if(typeof(func) == "undefined") document.getElementById(callback).innerHTML = loading + '...\n<br>\n<br>\n<br>\n<br>\n<br>\n<br>\n<br>\n<br>\n<br>\n<br>\n<br>\n<br>&nbsp;'; 
	XHR = new XHRConnection(); 
 	XHR.appendData(data, value); 
 	XHR.appendData(callback, 'on'); 
	XHR.datas = data;
	//alert(data + '=' + value)
	if(func)
	{
		XHR.sendAndLoad(page, method, func); 
// 		console.log('Fonction: ' + func)
	}
	else
	{
		XHR.sendAndLoad(page, method, remplirChamp); 
// 		console.log('Fonction: ' + remplirChamp)
	}
// 	boucle();
//  	document.getElementById('headers').innerHTML = wait; 
}

function doSwipe()
{
	alert('swipe');
}

function detectswipe(el,func) {
  swipe_det = new Object();
  swipe_det.sX = 0;
  swipe_det.sY = 0;
  swipe_det.eX = 0;
  swipe_det.eY = 0;
  var min_x = 20;  //min x swipe for horizontal swipe
  var max_x = 40;  //max x difference for vertical swipe
  var min_y = 40;  //min y swipe for vertical swipe
  var max_y = 50;  //max y difference for horizontal swipe
  var direc = "";
  ele = document.getElementById(el);
  ele.addEventListener('touchstart',function(e){
    var t = e.touches[0];
    swipe_det.sX = t.screenX; 
    swipe_det.sY = t.screenY;
  },false);
  ele.addEventListener('touchmove',function(e){
    //e.preventDefault();
    var t = e.touches[0];
    swipe_det.eX = t.screenX; 
    swipe_det.eY = t.screenY;    
  },false);
  ele.addEventListener('touchend',function(e){
    //horizontal detection
    if ((((swipe_det.eX - min_x > swipe_det.sX) || (swipe_det.eX + min_x < swipe_det.sX)) && ((swipe_det.eY < swipe_det.sY + max_y) && (swipe_det.sY > swipe_det.eY - max_y)))) {
      if(swipe_det.eX > swipe_det.sX) direc = "r";
      else direc = "l";
    }
    //vertical detection
    if ((((swipe_det.eY - min_y > swipe_det.sY) || (swipe_det.eY + min_y < swipe_det.sY)) && ((swipe_det.eX < swipe_det.sX + max_x) && (swipe_det.sX > swipe_det.eX - max_x)))) {
      if(swipe_det.eY > swipe_det.sY) direc = "d";
      else direc = "u";
    }

    if (direc != "") {
      if(typeof func == 'function') func(el,direc);
    }
    direc = "";
  },false);  
}

function myFunction(el,d) {
  alert("you swiped on element with id '"+el+"' to "+d+" direction");
}

accOrig=1;

function changeAcc(maxAcc = 4)
{
	accNew = accOrig + 1;
	if(accNew == maxAcc) accNew = 1;
	for(x=1;x<11;x++)
	{
		y = x;
		if(y == 10) y=0;
		oldEl = accOrig + '-form' + y;
// 		alert(oldEl);
		oldElB = accOrig + '-form' + y + 'B';
		if(document.getElementById(oldEl))
		{
			newEl = accNew + '-form' + y;
// 			alert(newEl);
			newElB = accNew + '-form' + y + 'B';
			aEl = document.getElementById(oldElB);
			bEl = document.getElementById(newElB);
			aEl.className = 'accno';
			aEl.accessKey = '';
			bEl.className = 'accyes';
			bEl.accessKey = y;
			//alert(oldEl + newEl);

		}
		else break;
	}
	accOrig += 1;
	if(accOrig == maxAcc) accOrig = 1;


}

function changeState(noDossier, toSet, value)
{
	sendData('nodossier=' + noDossier + "&changeState=on&toSet=" + toSet + "&value",  value, root + 'random_display.php', 'POST', "reloadPage", reloadPage);
}

function changeNewFile(noDossier)
{
	sendData('nodossier=' + noDossier + "&setNewFile",  'on', root + 'random_display.php', 'POST', "nouveaudossier");
}

function changeCity(zip, divtocallback)
{
	if (zip.length < 4) return;
//  	console.log(zip + ' = ' + zip.length)
	sendData('zip=' + zip + "&setNewCity",  'on', root + 'random_display.php', 'POST', divtocallback, remplirValue);
}

function verifymaj(tag)
{
	tagChk = tag + "_check";
	tagChk = tag + "";
	tagNew = tag + "_new";
	tagOld = tag + "_old";
	if(document.getElementById(tagChk).checked)
	{
		document.getElementById(tagNew).className='maj';
		document.getElementById(tagOld).className='majref';
	}
	else
	{
		document.getElementById(tagNew).className='majref';
		document.getElementById(tagOld).className='nomaj';
	}
}

// function activate(div, onglet, onglet2, bold)
function activate(div, onglet, onglet2, bold)
{
// 	if(sel)
// 	{
// 		alert(sel.id);
// 	}
	
// 	console.log(actonglet.id);
	onglets = ['onglet_client', 'onglet_pa', 'onglet_aut', 'otheronglet'];
	l = div.length - 1;
	
// 	if(div == '1' || div == '2' || div == '3' || div == '4' || div == '5')
// 	{
// 		if(actonglet.id == "otheronglet") return;
// 		else
// 		{
// 			console.log(actonglet.id);
// 			return;
// 		}
// 	}
	if(div == 'right' || div == 'left')
	{
// 		console.log("Actuellement: " + actonglet.id);
		for (o in onglets)
		{
			if(onglets[o] == actonglet.id)
			{
				if(div == 'right')
				{
					i = 1 + Number(o);
					newonglet = onglets[i];
// 					console.log("OK pour " + typeof(i));
					if(newonglet == undefined) newonglet = onglets[0];
				}
				if(div == 'left')
				{
					i = -1 + Number(o);
					newonglet = onglets[i];
// 					console.log("OK pour " + typeof(i));
					if(newonglet == undefined) newonglet = onglets[l];
				}
			}
// 			console.log(o + ':' + onglets[o]);
		}
// 		console.log(actonglet.id + ' -> ' + newonglet);
		if(newonglet == "otheronglet")
		{
			div = "otherdatas";
			onglet = "otheronglet";
			onglet2 = "otheronglet2";
			bold = "otheronglet2";
		}
		else
		{
			onglet = newonglet;
			newonglet = newonglet.replace("onglet_", "");
			div = "popup_" + newonglet + "1";
			onglet2 = "onglet2_" + newonglet;
			bold = "bold_" + newonglet + "1";
		}
	}
	
// 	console.log(div + ' ' + onglet + ' ' + onglet2 + ' ' + bold )

	actpersonne.className='popupguy';actpersonne=document.getElementById(div);actpersonne.className='popupguyshow';
	actonglet.style.fontWeight='normal';actonglet=document.getElementById(onglet);actonglet.style.fontWeight='bold';
	actonglet2.style.borderBottom='solid 1px';actonglet2=document.getElementById(onglet2);actonglet2.style.borderBottom='none';
	actbold.style.fontWeight='normal';actbold=document.getElementById(bold);actbold.style.fontWeight='bold';
	
	
// 	console.log(div + ' ' + onglet + ' ' + onglet2 + ' ' + bold )
	
}

function affCol()
{
	arrow = document.getElementById('arrow');
	colDatas = document.getElementById('colDatas');
// 	alert(colDatas.style.display)
	if(colDatas.style.display == "none")
	{
		arrow.innerHTML = "&larr;";
		arrow.style.textAlign = "left";
		colDatas.style.display = "table-cell";
	}
	else
	{
		arrow.innerHTML = "&rarr;";
		arrow.style.textAlign = "right";
		colDatas.style.display = "none";
	}
}

function openNote(idnote, copy, nouveau, dossier="", idop="")
{
	loc = 'note.php?idnote=' + idnote;
	if (copy) loc += '&copy=on';
	if (nouveau)
	{
		if(dossier) dossier = '&dossier=' + dossier;
		else dossier = '';
		if(idop) idop = '&idop=' + idop;
		else idop = '';
		loc = 'note.php?nouveau=on' + dossier + idop;
	}
	window.open(loc,'note','width=' + wNoteSize +',height=' + hNoteSize + ',toolbar=no,directories=no,menubar=no=no,location=no,status=no');
}

function sendGlobalSearch(page="resultat_recherche.php", id="searchGlobal")
{
	targ = document.getElementById(id);
	if(targ.value.length ==0)
	{
		document.getElementById('xhrRecherche').innerHTML = ''
	};
	if(targ.value.length > 2)
	{
		d=document.getElementById('dormant');
		dv='';
		if(dv)
		{
			for(i=0;i<d.options.length;i++)
			{
				if(d.options[i].selected)
				{
					dv += '&dormant[]=' + d.options[i].value
				}
			};
			if(document.getElementById('tousDormants').checked)
			{
				dv += '&tousDormants=1'
			};
		}
		sendData('searchGlobal=' + targ.value + dv + '&xhr','on', page, 'POST', 'xhrRecherche')
	}

}

function cycleCheckAll()
{
	checked = document.getElementById("cycleCheckBox").checked;
	el = document.getElementById("wrapoperations").getElementsByTagName("input");
	l = el.length;
	i2 = 0;
	for(i=0; i< l;i ++)
	{
		//console.log(i);
		eli = el.item(i).id;
		if(eli.substring(0, 19) == "norequete-multireq-")
		{
			i2 ++;
			//console.log(eli);
			document.getElementById(eli).checked = checked;
			select_color(i2, eli);
		}
		//try
		//(({
		//	console.log(el[i].innerHTML);
		//}
		//catch(error)
		//{
		//	console.error(error);
		//}
	}
}

function newFocus()
{
	//console.log(af);
	document.getElementById(af).focus();
	if(af == 'date_jour')
	{
		af = 'add-op';
	}else{
		af = 'date_jour';
	}
}

/*function abandon(noDossier, aAbandonner)
{
	sendData('nodossier=' + noDossier + "&abandon=on&aabandonner",  aAbandonner, './random_display.php', 'POST', "reloadPage", reloadPage);
}

function facturation(noDossier, aFacturer)
{
	//sendData('nodossier=' + noDossier + "&facturation=on&afacturer",  aFacturer, 'ckup/
	/
	./random_display.php', 'POST', 'champ' + noDossier);
	sendData('nodossier=' + noDossier + "&facturation=on&afacturer",  aFacturer, './random_display.php', 'POST', "reloadPage", reloadPage);
}*/
