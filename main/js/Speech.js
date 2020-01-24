var infoBox; // 訊息 label
var textBox; // 最終的辨識訊息 text input
var tempBox; // 中間的辨識訊息 text input
var startStopButton; // 「辨識/停止」按鈕
var final_transcript = ''; // 最終的辨識訊息的變數
var recognizing = false; // 是否辨識中
var k = 0;
var wallet_type = '';
var ifm    = '';
var ifm_cD = '';
var ary_plu_str = Array('家','加','街','接');
var ary_min_str = Array('簡','剪','減');
var ary_mul_str = Array('乘','乘以','成','層');
var ary_exc_str = Array('除','除以');



window.onload = (function () {
	ifm        = document.getElementById('if_idx');
	ifm.height = ifm.contentWindow.document.documentElement.scrollHeight;
	
});

function creatRequestObj(){var a=null;try{a=new XMLHttpRequest}catch(c){try{a=new ActiveXObject("Msxml2.XMLHTTP")}catch(b){try{a=new ActiveXObject("Microsoft.XMLHTTP")}catch(d){a=null}}}return a}

function startButton(event) {
	infoBox = document.getElementById("infoBox"); // 取得訊息控制項 infoBox
	textBox = document.getElementById("textBox"); // 取得最終的辨識訊息控制項 textBox
    tempBox = document.getElementById("tempBox"); // 取得中間的辨識訊息控制項 tempBox
    startStopButton = document.getElementById("startStopButton"); // 取得「辨識/停止」這個按鈕控制項
    langCombo = document.getElementById("langCombo"); // 取得「辨識語言」這個選擇控制項
  if (recognizing) { // 如果正在辨識，則停止。
    recognition.stop();
  } else { // 否則就開始辨識
    textBox.value = ''; // 清除最終的辨識訊息
    tempBox.value = ''; // 清除中間的辨識訊息
    final_transcript = ''; // 最終的辨識訊息變數
    recognition.lang = langCombo.value; // 設定辨識語言
    recognition.lang = "cmn-Hant-TW"; // 設定辨識語言
    recognition.start(); // 開始辨識
  }
}

if (!('webkitSpeechRecognition' in window)) {  // 如果找不到 window.webkitSpeechRecognition 這個屬性
  // 就是不支援語音辨識，要求使用者更新瀏覽器。 
  infoBox.innerText = "本瀏覽器不支援語音辨識，請更換瀏覽器！(Chrome 25 版以上才支援語音辨識)";
} else {
  var recognition = new webkitSpeechRecognition(); // 建立語音辨識物件 webkitSpeechRecognition
  recognition.continuous = true; // 設定連續辨識模式
  recognition.interimResults = true; // 設定輸出中先結果。

  recognition.onstart = function() { // 開始辨識
    recognizing = true; // 設定為辨識中
    startStopButton.value = "按此停止"; // 辨識中...按鈕改為「按此停止」。  
    infoBox.innerText = "辨識中...";  // 顯示訊息為「辨識中」...
  };

  recognition.onend = function() { // 辨識完成
    recognizing = false; // 設定為「非辨識中」
    startStopButton.value = "開始辨識";  // 辨識完成...按鈕改為「開始辨識」。
    infoBox.innerText = ""; // 不顯示訊息
  };

  recognition.onresult = function(event) { // 辨識有任何結果時
    var interim_transcript = ''; // 中間結果
    var text_box_len = textBox.value.length;
    for (var i = event.resultIndex; i < event.results.length; ++i) { // 對於每一個辨識結果
      if (event.results[i].isFinal) { // 如果是最終結果
        final_transcript += event.results[i][0].transcript; // 將其加入最終結果中
        final_test_transcript = event.results[i][0].transcript; // 將其加入最終結果中
      } else { // 否則
        interim_transcript += event.results[i][0].transcript; // 將其加入中間結果中
      }
    }
    if (final_transcript.trim().length > 0){ // 如果有最終辨識文字
        textBox.value = final_transcript; // 顯示最終辨識文字
        testBox.value = final_test_transcript;
    }
    if (interim_transcript.trim().length > 0){ // 如果有中間辨識文字
        tempBox.value = interim_transcript; // 顯示中間辨識文字
    }
    
    
    if ( textBox.value.length > text_box_len ){
    	text_box_len = textBox.value.length;
    	final_test_transcript = final_test_transcript.trim();
    	$(document).attr("title", final_test_transcript);
    	
    	if ( final_test_transcript == '記帳' ){
    		document.getElementById('if_idx').src = "index.php?sel_type=wallet&sel_type1=wallet";
    	}
        if ( final_test_transcript == '程式碼' ){
        	document.getElementById('if_idx').src = "index.php?sel_type=code_link&sel_type1=php";
    	}
        if ( final_test_transcript == '行事曆' ){
        	document.getElementById('if_idx').src = "index.php?sel_type=calendar&sel_type1=calendar";
    	}
        if ( final_test_transcript == '圖片欣賞' ){
        	document.getElementById('if_idx').src = "index.php?sel_type=girls&sel_type1=girls";
    	}
    	if ( final_test_transcript == '小程式' ){
    		document.getElementById('if_idx').src = "index.php?sel_type=machi&sel_type1=machi";
    	}
    	if ( final_test_transcript == '天上碑' ){
    		document.getElementById('if_idx').src = "index.php?sel_type=txb&sel_type1=txb";
    	}
    	
    	if ( ( final_test_transcript.indexOf('元') != -1 || final_test_transcript.indexOf('塊') != -1 ) ){
    		if ( final_test_transcript.indexOf('元') != -1 ){
    			var ary = final_test_transcript.split('元');
    		} else if ( final_test_transcript.indexOf('塊') != -1 ){
    			var ary = final_test_transcript.split('塊');
    		}
    		
    		for ( var i = 1; i <= ary[0].length; i++  ){
    			if ( isNaN(ary[0].substr(-1*i,1)) ){
    				i--;
    				break;
    			}
    		}
    		
    		ifm.contentDocument.getElementById("inp_content").value = ary[0].substr(0,ary[0].length - i);
    		ifm.contentDocument.getElementById("inp_remark").value  = ary[1];
    		ifm.contentDocument.getElementById("inp_money").value   = ary[0].substr(-1*i);
    		wallet_type = 'insert_type';
    	}
        
        if ( final_test_transcript.indexOf('刪除編號') != -1 ){
        	var del_num = final_test_transcript.replace('刪除編號','');
        	if(confirm('確定刪除'+del_num+'號?')){
        		ifm.contentWindow.HGP('del','click_wallet='+del_num,5,'form1');}else{void(0);
        	}
        }
        if ( final_test_transcript == '收入' ){
        	ifm.contentWindow.HGP('ins','sel_type=wallet,sel_type1=wallet,money_type=p',2,'form_ins');
        }
        if ( final_test_transcript == '支出'  ){
        	ifm.contentWindow.HGP('ins','sel_type=wallet,sel_type1=wallet,money_type=m',2,'form_ins');
        }
        
        if ( final_test_transcript.indexOf('google搜尋') != -1 ){
        	var query_str = final_test_transcript.replace('google搜尋','');
        	var link_str  = 'https://www.google.com/webhp?ie=utf-8&oe=utf-8#q=' + query_str ;
        	window.open(link_str,'_blank','');
        }
        if ( final_test_transcript.indexOf('youtube搜尋') != -1 ){
        	var query_str = final_test_transcript.replace('youtube搜尋','');
        	var link_str  = 'https://www.youtube.com/results?search_query=' + query_str ;
        	window.open(link_str,'_blank','');
        }
        if ( final_test_transcript.indexOf('google地圖') != -1 ){
        	var query_str = final_test_transcript.replace('google地圖','');
        	var link_str  = 'http://maps.google.com.tw/maps?f=q&hl=zh-TW&q=' + query_str ;
        	window.open(link_str,'_blank','');
        }
        
        if ( final_test_transcript == '開啟卡提諾' ){
        	var link_str  = 'http://ck101.com/';
    		window.open(link_str,'_blank','');
        }
    	if ( final_test_transcript.indexOf('開啟')   != -1 && 
    		 final_test_transcript.indexOf('資料夾') != -1 ){
    		var tmp_ary = Array();
    		tmp_ary[0] = '1';
    		tmp_ary[1] = final_test_transcript;
        	ajax_speech(tmp_ary);
        }
    	if ( final_test_transcript.indexOf('執行') != -1 ){
       		var tmp_ary = Array();
       		tmp_ary[0] = 2;
       		tmp_ary[1] = final_test_transcript;
           	ajax_speech(tmp_ary);
        }
    	if ( final_test_transcript.indexOf('第') != -1 && 
       		 final_test_transcript.indexOf('頁') != -1 ){
    		var str1 = final_test_transcript.split('第')[1];
       		var str2 = str1.split('頁')[0];
       		var num  = parseInt(str2);
       		ifm.contentWindow.HGP('index','sel_page='+num,5,'form_wallet');
        }
		if ( final_test_transcript == '上一頁' || final_test_transcript == '下一頁'|| final_test_transcript == '下頁'|| final_test_transcript == '上頁'){
    	    var num        = $(ifm.contentDocument.getElementById("sel_page")).find(":selected").val();
			var sel_length = ifm.contentDocument.getElementById("sel_page").length;
			num = parseInt(num);
			
			if ( final_test_transcript == '上一頁' || final_test_transcript == '上頁' ){
				num = num > 1 ? num-1 : num;
				ifm.contentWindow.HGP('index','sel_page='+num,5,'form_wallet');
			} else if ( final_test_transcript == '下一頁' || final_test_transcript == '下頁' ){
				num = num < sel_length ? num+1 : num;
				ifm.contentWindow.HGP('index','sel_page='+num,5,'form_wallet');
			}
        }
    	
    	if ( !isNaN(final_test_transcript.substr(0,1)) && !isNaN(final_test_transcript.substr(-1,1)) ){
    		var str_len = final_test_transcript.length;
    		var cal_str = '';
    		var get_1 = 0,get_2 = 0;
    		var num_1 = 0,num_2 = 0;
    		var sum   = 0;
    		for (var i=0; i < str_len; i++ ){
    			if ( isNaN(final_test_transcript.substr(i,1)) ){
    				cal_str += final_test_transcript.substr(i,1);
    				get_2 = i;
    			}
    		}
    		get_1 = get_2 - cal_str.length;
    		num_1 = parseInt(final_test_transcript.substr(0, get_1 +1));
    		num_2 = parseInt(final_test_transcript.substr(get_2 +1, final_test_transcript.length));
    		
    		if ( ary_plu_str.indexOf(cal_str) != -1 ){
    			sum = num_1 + num_2;
    		} else if ( ary_min_str.indexOf(cal_str) != -1 ){
    			sum = num_1 - num_2;
    		} else if ( ary_mul_str.indexOf(cal_str) != -1 ){
    			sum = num_1 * num_2;
    		} else if ( ary_exc_str.indexOf(cal_str) != -1 ){
    			sum = num_1 / num_2;
    		}
    		testBox.value = sum;
    	}
    }
  };
}





function ajax_speech(ary){
	var b = new creatRequestObj();
	if ( ary[0] == 1 ){
	  //b.open("GET","ajax_speech.php?act=code_link&sno="+sno+"&old_click="+old_click+"&"+Date.parse(new Date),!0);
		var folder_name = ary[1].split('開啟')[1];
		folder_name = encodeURIComponent(folder_name.split('資料夾')[0]+"   ");
		b.open("GET","ajax_speech.php?act=open_dir&val1="+folder_name+"&"+Date.parse(new Date),!0);
	} else if ( ary[0] == 2 ) {
		var folder_name = ary[1].split('執行')[1];
		b.open("GET","ajax_speech.php?act=open_exe&val1="+folder_name+"&"+Date.parse(new Date),!0);
	}
	
	b.onreadystatechange=function(){
	  if(b.readyState==4){
	    var a=b.responseText;
	    if(ary[0]==1){
		  var get=Trim(a).split(",");
		  //alert(get[0]);
		}
	  }	
	}
	b.send(null);
  }
