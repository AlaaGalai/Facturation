var actMode = 'nouveau';
var actAct = 'Ajouter vol.'
var oldAct = 'Modifier'
var tenth = 0;
var act = ''

function changeMode()
{
	console.clear();
// 	alert('on');
	oldMode = actMode;
	act = oldAct
	oldAct = actAct
	actAct = act
	if (actMode == 'nouveau')
	{
		actMode = 'modifier';
	}
	else
	{
		actMode = 'nouveau';
	}


	for(x=0;;x++)
	{
		y = x + 1;
		aacc = actMode + 'acc' + y;
		oacc = oldMode + 'acc' + y;
		console.log(aacc);
		if( ! document.getElementById(aacc)) break;
		else
		{
			if(document.getElementById(oacc).accessKey)
			{
				document.getElementById(aacc).accessKey = document.getElementById(oacc).accessKey;
				document.getElementById(oacc).accessKey = '';
				document.getElementById(aacc).innerHTML += ' (alt - ' + document.getElementById(aacc).accessKey + ')';
				v = document.getElementById(oacc).innerHTML;
				reg = /\(alt - [0-9]\)/g;
				r = v.replace(reg, '')
				document.getElementById(oacc).innerHTML = r;
			}
		}

	}
}
function changeTenth(moins)
{
	console.clear();
	if (moins) tenth -= 1;
	else tenth += 1;
	if(tenth <0) tenth=0;
	for(x=0;;x++)
	{
		y = x + 1;
		racc = actMode + 'acc' + y;
		if( ! document.getElementById(racc)) break;
	}
	if((tenth * 10) > (x -1)) tenth = Math.floor((x - 1)/10);
	
	for(x=0;;x++)
	{
		min = tenth * 10 + 1;
		max = tenth * 10 + 10;
		y = x + 1;
		racc = actMode + 'acc' + y;
// 		console.log(racc);
		if( ! document.getElementById(racc)) break;
		if (y < min) spx = -1;
		else if (y == min) spx = 1;
		else if (y < max) spx++;
		else if (y == max) spx = 0;
		else if (y > max) spx = -1;
		console.log(spx);
		if (spx != -1)
		{
			v = document.getElementById(racc).innerHTML;
			reg = /\(alt - [0-9]\)/g;
			r = v.replace(reg, '')
			document.getElementById(racc).innerHTML = r +' (alt - ' + spx + ')';
			document.getElementById(racc).accessKey = '' + spx;
		}
		else
		{
			v = document.getElementById(racc).innerHTML;
			reg = /\(alt - [0-9]\)/g;
			r = v.replace(reg, '')
			console.log(v + ',' + r);
			document.getElementById(racc).innerHTML = r;
			document.getElementById(racc).accessKey =  '';
		}
	}
}
