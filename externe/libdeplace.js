// Temporary variables to hold mouse x-y pos.
var iMousePosX = 0;
var iMousePosY = 0;

var iOrigObjTop;
var iOrigObjLeft;

var iDragObjTopDiff;
var iDragObjLeftDiff;

var rightclick = false;
var oDragObj = null;
var drag = false;

// Start dragging
function dragPiece(sourceObject, id)
{
	// Remember original object position.
	iOrigObjTop = (parseInt(sourceObject.style.top))?parseInt(sourceObject.style.top):0;
	iOrigObjLeft = (parseInt(sourceObject.style.left))?parseInt(sourceObject.style.left):0;
	
	plageid = 'plage' + id;
	heuredebutid = 'heuredebut' + id;
	heurefinid = 'heurefin' + id;
	minutedebutid = 'minutedebut' + id;
	minutefinid = 'minutefin' + id;
	formid = 'submit' + id;
	
	origHeureDebut = document.getElementById(heuredebutid).value;
	origHeureFin   = document.getElementById(heurefinid).value;
	origMinuteDebut = document.getElementById(minutedebutid).value;
	origMinuteFin = document.getElementById(minutefinid).value;
// 	alert (origHeureDebut + ',' + origHeureFin + ',' + origMinuteDebut + ',' + origMinuteFin);
// 	origHeureFin = parseInt(heurefindi);
// 	origMinuteDebut = parseInt(minutedebutid);
// 	origMinuteFin = parseInt(minutefinid);*/

	
	iDragObjTopDiff = iMousePosY - iOrigObjTop;
	iDragObjLeftDiff = iMousePosX - iOrigObjLeft;
	
	oDragObj = sourceObject;
}

// Stop dragging
function drop()
{
	var ecart = 15;
	var premiereHeure = 6;
	var premiereMinute = 0;
	var derniereHeure = 21;
	var derniereMinute = 45;
	
	oDragObj = null;
	
	nbEcarts = Math.floor((iMousePosY - iDragObjTopDiff)/20);
	if((iMousePosY - iDragObjTopDiff) > 10 || (iMousePosY - iDragObjTopDiff) < -10) drag = true;
//	alert(iMousePosY - iDragObjTopDiff);
	nbHeuresEcart = Math.floor(nbEcarts/4);
	nbMinutesEcart = (parseInt(nbEcarts,10) - 4 * nbHeuresEcart)* ecart;
	
	finHeureDebut = parseInt(origHeureDebut,10) + parseInt(nbHeuresEcart,10);
	finMinuteDebut = parseInt(origMinuteDebut,10) + parseInt(nbMinutesEcart,10);
	if(finMinuteDebut < 0)
	{
		finHeureDebut -= 1;
		finMinuteDebut = 60 - finMinuteDebut;
	}
	
	if(finMinuteDebut > 59)
	{
		finHeureDebut += 1;
		finMinuteDebut -= 60;
	}
/*	if(finHeureDebut < premiereHeure)
	{
		finHeureDebut = premiereHeure;
		if(finMinuteDebut < premiereMinute) finMinuteDebut = premiereMinute;
	}*/
	
	finHeureFin = parseInt(origHeureFin,10) + parseInt(nbHeuresEcart,10);
	finMinuteFin = parseInt(origMinuteFin,10) + parseInt(nbMinutesEcart,10);
	if(finMinuteFin < 0)
	{
		finHeureFin -= 1;
		finMinuteFin = 60 - finMinuteFin;
	}
	
	if(finMinuteFin > 59)
	{
		finHeureFin += 1;
		finMinuteFin -= 60;
	}
/*	if(finHeureFin > derniereHeure) finHeureFin = derniereHeure;
	if(finMinuteFin > derniereMinute) finMinuteFin = derniereMinute;*/
	document.getElementById(heuredebutid).value = finHeureDebut;
	document.getElementById(heurefinid).value = finHeureFin;
	document.getElementById(minutedebutid).value = finMinuteDebut;
	document.getElementById(minutefinid).value = finMinuteFin;
	if(drag == true) document.getElementById(formid).submit();
	
	document.getElementById(plageid).innerHTML= finHeureDebut + ':' + finMinuteDebut + ' - ' + finHeureFin + ':' + finMinuteFin;// + Math.floor((iMousePosY - iDragObjTopDiff)/80);
}

function findPosition( oLink ) {
  if( oLink.offsetParent ) {
    for( var posX = 0, posY = 0; oLink.offsetParent; oLink = oLink.offsetParent ) {
      posX += oLink.offsetLeft;
      posY += oLink.offsetTop;
    }
    return [ posX, posY ];
  } else {
    return [ oLink.x, oLink.y ];
  }
}

// // Handle mouse key down.
// function mouseDown(e) {
// if (!e) var e = window.event;
// if (e.which) rightclick = (e.which == 3);
// else if (e.button) rightclick = (e.button == 2);
// 
// if (rightclick) {
// // document.getElementById("mousestat").innerHTML = "right down";
// bMouseRightKeyDown = true;
// } else {
// // document.getElementById("mousestat").innerHTML = "left down";
// bMouseLeftKeyDown = true;
// }
// return false;
// }
// 
// // Handle mouse key up.
// function mouseUp(e) {
// if (!e) var e = window.event;
// if (e.which) rightclick = (e.which == 3);
// else if (e.button) rightclick = (e.button == 2);
// 
// if (rightclick) {
// // document.getElementById("mousestat").innerHTML = "right up";
// bMouseRightKeyDown = false;
// } else {
// // document.getElementById("mousestat").innerHTML = "left up";
// bMouseLeftKeyDown = false;
// }
// return false;
// }