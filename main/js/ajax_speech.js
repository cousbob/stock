function get_speech( speech ){
	if ( speech == '記帳' ){
		location.href = "index.php?sel_type=wallet&sel_type1=wallet";
	}
	if ( speech == '記事本' ){
		alert(speech);
	}
	
	if ( speech == '選擇內容' ){
		var tmp_id = chk_id_exist("inp_content");
		if ( tmp_id ){
			tmp_id.focus();
		}
	}
}

function chk_id_exist(id){
	var tmp_id = document.getElementById(id);
	if ( !tmp_id ){
		return false;
	} 
	return tmp_id;
}