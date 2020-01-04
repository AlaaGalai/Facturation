var viewportwidth;
var viewportheight;
  
// the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight
 
if (typeof window.innerWidth != 'undefined')
{
	viewportheight = window.innerHeight;
	viewportwidth = window.innerWidth;
}
 
// IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)

else if (typeof document.documentElement != 'undefined'
&& typeof document.documentElement.clientWidth !=
'undefined' && document.documentElement.clientWidth != 0)
{
	viewportwidth = document.documentElement.clientWidth,
	viewportheight = document.documentElement.clientHeight
}
 
// older versions of IE
 
else
{
	viewportwidth = document.getElementsByTagName('body')[0].clientWidth,
	viewportheight = document.getElementsByTagName('body')[0].clientHeight
}

// document.write('<p>Your viewport width is '+viewportwidth+'x'+viewportheight+'</p>');

window.addEventListener('load', function(){
	var box1 = document.getElementById('body');
	var startx = 0
	var distx = 0
	var limitx = viewportwidth/3 * 2
	var starty = 0
	var disty = 0
	var limity = viewportheight/2
	var fingers = 0
 
	box1.addEventListener('touchstart', function(e){
		var touchobj = e.changedTouches[0] // reference first touch point (ie: first finger)
		fingers = e.touches.length
		startx = parseInt(touchobj.clientX) // get x position of touch point relative to left edge of browser
		starty = parseInt(touchobj.clientY) // get y position of touch point relative to top edge of browser
	}, false)

	box1.addEventListener('touchmove', function(e){
		var touchobj = e.changedTouches[0] // reference first touch point for this event
		var distx = parseInt(touchobj.clientX) - startx
		var disty = parseInt(touchobj.clientY) - starty

		if(e.touches.length == 1 && fingers == 1) // only if one finger touches the screen
		{
			if(disty > limity)
			{
				document.getElementById('body').style.backgroundColor='#ffffd0';
				window.location.reload() //('Status: left' + e.touches.length)
			}
			else if(distx > limitx)
			{
				document.getElementById('body').style.backgroundColor='#d0d0d0';
				document.getElementById('moins').submit() //('Status: left' + e.touches.length)
			}
			else if(distx < - limitx)
			{
				document.getElementById('body').style.backgroundColor='#d0d0d0';
				document.getElementById('plus').submit() //alert('Status: right' + e.touches.length)
			}
		}
	}, false)
}, false)
