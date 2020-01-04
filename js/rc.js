function choixSociete(nosociete, canton, nom, typeIndex, indexId)
{
// 	oldtype = window.opener.document.getElementById('typesociete');
// 	for (i=0;i<10;i++) alert (oldtype[i].value);
	if(typeIndex != '') window.opener.document.getElementById('typesociete'+indexId).selectedIndex = typeIndex;
	if(nosociete != '') window.opener.document.getElementById('nosociete'+indexId).value = nosociete;
	if(canton != '') window.opener.document.getElementById('canton'+indexId).value = canton;
	if(nom != '') window.opener.document.getElementById('nom'+indexId).value = nom;
	self.close();
	
}