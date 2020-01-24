// JavaScript Document
var get_ball = QueryString("get_ball");


//抓取QueryString的函式
function QueryString(name) 
{
    var AllVars = window.location.search.substring(1);
    var Vars = AllVars.split("&");
    for (i = 0; i < Vars.length; i++)
    {
        var Var = Vars[i].split("=");
        if (Var[0] == name) return Var[1];
    }
    return "";    
}

//球類的轉移視窗
function SS_go(){
    var QS = window.location.search;
    alert(QS);
    //document.location=;
}

//開啟"設定"視窗
function opSet(page,x,y){
    window.open(page, "FCIF","height="+y+",width="+x+",resizable=yes,status=no,alwaysRaised=yes,z-look=yes");
}


//數字轉中文
function num2str(num){
    var str = null;
    switch(num){
        case 0:     str="零";
        case 1:     str="壹";
        case 2:     str="貳";
        case 3:     str="参";
        case 4:     str="肆";
        case 5:     str="伍";
        case 6:     str="陸";
        case 7:     str="柒";
        case 8:     str="捌";
        case 9:     str="玖";
        case 10:     str="拾";
        case 100:     str="佰";
        case 100:     str="仟";
        case 1000:     str="萬";
    }
    return str;
}

//HGP(網頁名稱(不加.php) , 該控制項id or 欲增加或修改的變數 , type , form的id )  結果:加入QueryString以及送出表單
//type=0  : 原get                                    HGP('main','id'            ,0  ,'form1')
//type=1  : select選項                                HGP('main','id'            ,1  ,'form1')
//type=2  : 修改/增加選項                                HGP('main','id=01'        ,2  ,'form1')
//type=3  : 去掉id選項                                HGP('main','del'        ,3  ,'form1')
//type=4  : 原get                    ,不送出表單        HGP('main','id'            ,4  ,'form1')
//type=5  : 修改/增加選項                ,不送出表單        HGP('main','id=15'        ,5  ,'form1')
//type=6  : 去掉id選項                ,不送出表單        HGP('main','del'        ,6  ,'form1')
//type=7  : 修改+去掉id選項                            HGP('main','id=10#del'    ,7  ,'form1')
//type=8  : 修改+去掉id選項            ,不送出表單        HGP('main','id=10#del'    ,8  ,'form1')
//type=9  : 只保留帶入參數             ,不送出表單        HGP('main','id=10'        ,9  ,'form1')
//type=10 : 立即抓取text值,加入get值                    HGP('main','id=10'        ,10 ,'form1')
//type=11 : select選項                ,不送出表單        HGP('main','id'            ,11 ,'form1')
//type=12 : select選項                ,                HGP('main','id#del'        ,12 ,'form1')
//type=13 : select選項                ,不送出表單        HGP('main','id#del'        ,13 ,'form1')
function HGP(page,id,type,name){
    var QS = window.location.search.substring(1);
    if(type==0||type==4){
        if(!QS){}else QS = "?" + QS;
        if(type==0)$("#"+name).attr("action",page+".php"+QS).submit();
        else location.href=page+".php"+QS;
    }else if(type==1||type==11){
        var str = QS.split("&");
        var str1="";
        var j = null;
        for(var i = 0;i<str.length;i++){if((str[i].split("="))[0].match("^"+id+"$")!=null){j=i;break;}}
        if(typeof j=="null"){
            str1 = QS;    
        }else{
            for(var i = 0;i<str.length;i++){
                if(i!=j)                         str1 += str[i];
                if(str1&&i!=str.length-1&&i!=j)    str1 += "&"; 
            }
        }
        if(str1){
            if(str1.indexOf("&&")>0){str1 = str1.replace("&&","&")}
            if(str1.substr((str1.length-1),1)=="&"){str1 = str1.substr(0,str1.length-1);}
            QS = "?"+str1+"&"+id+"="+$("#"+id+" :selected").val();
        }else{
            QS = "?"+id+"="+$("#"+id+" :selected").val();
        }
        
        if(type==1)    $("#"+name).attr("action",page+".php"+QS).submit();
        else location.href=page+".php"+QS;
    }else if(type==2||type==5){
        var id1  = id.split(",");
        var str  = QS.split("&");
        var id2  = Array();
        var j    = Array();
        var str1 = "";
        for(var i=0;i<id1.length;i++){id2.push(id1[i].split("="));}//id2存各個修改的數值
        for(var i=0;i<(!str?1:str.length);i++){for(var k=0;k<id1.length;k++){if((str[i].split("="))[0].match("^"+id2[k][0]+"$")!=null){j.push(i);break;}}}    //檢查在哪幾個位置存入j
        if(typeof j=="null"){
            str1 = QS;
        }else{
            for(var i=0;i<str.length;i++){if(j.indexOf(i)==-1){str1 += str[i]+"&";}}                        //如果i是指定位置就會跳過
        }
        if(str1.indexOf("&&")>0){str1 = str1.replace("&&","&")}
        if(str1.substr((str1.length-1),1)=="&"){str1 = str1.substr(0,str1.length-1);}
        if(str1!=""){    
            QS = "?"+str1;
            for(var i=0;i<id1.length;i++){QS += "&"+id2[i][0]+"="+id2[i][1];}                                //將新增或修改的項目加入QS
        }else{
            QS = "?";
            for(var i=0;i<id1.length;i++){if(i==0){QS += id2[i][0]+"="+id2[i][1];}else {QS += "&"+id2[i][0]+"="+id2[i][1];}}
        }
        if(type==2)$("#"+name).attr("action",page+".php"+QS).submit();
        else location.href=page+".php"+QS;
    }else if(type==3||type==6){
        var id1   = id.split(",");
        var str   = QS.split("&");
        var j       = Array();
        var str1  = "";
        
        for(var i=0;i<(!str?1:str.length);i++){for(var k=0;k<id1.length;k++){if((str[i].split("="))[0].match("^"+id1[k]+"$")!=null){j.push(i);break;}}}    
        //檢查在哪幾個位置存入j
        
        if(typeof j=="null"){
            str1 = QS;    
        }else{
            for(var i=0;i<str.length;i++){if(j.indexOf(i)==-1){str1+=str[i]+"&";}}                        //如果i是指定位置就會跳過
        }        
        if(str1.indexOf("&&")>0){str1 = str1.replace("&&","&")}
        if(str1.substr((str1.length-1),1)=="&"){str1 = str1.substr(0,str1.length-1);}
        QS = "?" + str1;
        if(type==3)$("#"+name).attr("action",page+".php"+QS).submit();
        else location.href=page+".php"+QS;
    }else if(type==7||type==8){
        var x      = id.split("#");
        var x1   = x[1].split(",")
        var id1  = x[0].split(",");
        var str  = QS.split("&");
        var id2  = Array();
        var j     = Array();
        var str1 = "";
        for(var i=0;i<id1.length;i++){id2.push(id1[i].split("="));}                                                                //id2存各個修改的數值
        for(var i=0;i<(!str?1:str.length);i++){for(var k=0;k<id1.length;k++){if((str[i].split("="))[0].match("^"+id2[k][0]+"$")!=null){j.push(i);break;}}}    //檢查在哪幾個位置存入j
        for(var i=0;i<(!str?1:str.length);i++){for(var k=0;k<x1.length;k++){if((str[i].split("="))[0].match("^"+x1[k]+"$")!=null){j.push(i);break;}}}
        if(typeof j=="null"){
            str1 = QS;
        }else{
            for(var i=0;i<str.length;i++){if(j.indexOf(i)==-1){str1 += str[i]+"&";}}                        //如果i是指定位置就會跳過
        }
        if(str1.indexOf("&&")>0){str1 = str1.replace("&&","&")}
        if(str1.substr((str1.length-1),1)=="&"){str1 = str1.substr(0,str1.length-1);}
        if(str1!=""){    
            QS = "?"+str1;
            for(var i=0;i<id1.length;i++){QS += "&"+id2[i][0]+"="+id2[i][1];}                                //將新增或修改的項目加入QS
        }else{
            QS = "?";
            for(var i=0;i<id1.length;i++){if(i==0){QS += id2[i][0]+"="+id2[i][1];}else{QS += "&"+id2[i][0]+"="+id2[i][1];}}
        }
        if(type==7)$("#"+name).attr("action",page+".php"+QS).submit();
        else location.href=page+".php"+QS;
    }else if(type==9){
        var id1  = id.split(",");
        var id2  = Array()
        for(var i=0;i<id1.length;i++){id2.push(id1[i].split("="));}                                                                //id2存各個修改的數值
        QS = "?";
        for(var i=0;i<id1.length;i++){if(i==0){QS += id2[i][0]+"="+id2[i][1];}else {QS += "&"+id2[i][0]+"="+id2[i][1];}}
        location.href=page+".php"+QS;
    }else if(type==10){
        var id1  = id.split(",");
        var str  = QS.split("&");
        var id2  = Array();
        var j     = Array();
        var str1 = "";
        for(var i=0;i<id1.length;i++){id2.push(id1[i].split("="));}                                                                //id2存各個修改的數值
        for(var i=0;i<(!str?1:str.length);i++){for(var k=0;k<id1.length;k++){if((str[i].split("="))[0].match("^"+id2[k][0]+"$")!=null){j.push(i);break;}}}    //檢查在哪幾個位置存入j
        if(typeof j=="null"){
            str1 = QS;
        }else{
            for(var i=0;i<str.length;i++){if(j.indexOf(i)==-1){str1 += str[i]+"&";}}                        //如果i是指定位置就會跳過
        }
        if(str1.indexOf("&&")>0){str1 = str1.replace("&&","&")}
        if(str1.substr((str1.length-1),1)=="&"){str1 = str1.substr(0,str1.length-1);}
        if(str1!=""){    
            QS = "?"+str1;
            for(var i=0;i<id1.length;i++){QS += "&"+id2[i][0]+"="+$("#"+id2[i][0]).val();}                                //將新增或修改的項目加入QS
        }else{
            QS = "?";
            for(var i=0;i<id1.length;i++){if(i==0){QS += id2[i][0]+"="+id2[i][1];}else {QS += "&"+id2[i][0]+"="+id2[i][1];}}
        }
        $("#"+name).attr("action",page+".php"+QS).submit();
    }else if(type==12||type==13){
        var x      = id.split("#");
        var x1   = x[1].split(",")
        //var id1  = x[0].split(",");
        var id1  = x[0]; 
        var str  = QS.split("&");
        var id2  = Array();
        var j    = Array();
        var str1 = "";        
        //for(var i=0;i<id1.length;i++){id2.push(id1[i].split("="));}                                                                //id2存各個修改的數值
        for(var i=0;i<(!str?1:str.length);i++){for(var k=0;k<id1.length;k++){if((str[i].split("="))[0].match("^"+id1+"$")!=null){j.push(i);break;}}}    //檢查在哪幾個位置存入j
        for(var i=0;i<(!str?1:str.length);i++){for(var k=0;k<x1.length;k++){if((str[i].split("="))[0].match("^"+x1[k]+"$")!=null){j.push(i);break;}}}
        if(typeof j=="null"||!j){
            str1 = QS;
        }else{
            for(var i=0;i<str.length;i++){if(j.indexOf(i)==-1){str1 += str[i]+"&";}}                        //如果i是指定位置就會跳過
        }
        if(str1.indexOf("&&")>0){str1 = str1.replace("&&","&")}
        if(str1.substr((str1.length-1),1)=="&"){str1 = str1.substr(0,str1.length-1);}
        if(str1!=""){                                                                                        //將新增或修改的項目加入QS
            QS = "?"+str1+"&"+id1+"="+$("#"+id1+" :selected").val();                                
        }else{
            QS = "?"+id1+"="+$("#"+id1+" :selected").val();
        }
        if(type==12)$("#"+name).attr("action",page+".php"+QS).submit();
        else location.href=page+".php"+QS;
    }
}

//更新倒數
function ref(htp){
    var vp = QueryString("view_page");
    if(vp!="bg_odds1"){
        var t = $("#per_sec").find(":selected").val();
        if(typeof t =="null"||typeof t =="undefined"){
        }else{
            if(t==999){
                clearInterval(ref_timer);
                $("#up_sec").html("N");
            }else {
                t--;
                var ref_timer = setInterval(
                function(){
                    if(t>0){
                        $("#up_sec").html(""+(t--));
                    }else if(t==0){
                        t--;
                        if(htp=="main"){
                            HGP(htp,'per_sec#act,val,bSno,bgt,team,mod',12,'form1');
                        }else if(htp=="main_mem"){
                            HGP(htp,'per_sec',4,'form1');
                        }else{
                            HGP(htp,'per_sec',11,'form1');
                        }
                    }
                },1000);
            }
        }
    }
}

//check_all(this,被指定的group[name要有]),checkbox全勾選
function check_all(obj,cName){ 
    var checkboxs = document.getElementsByName(cName); 
    for(var i=0;i<checkboxs.length;i++){checkboxs[i].checked = obj.checked;} 
} 

//使得indexOf 可以正常使用
if (!Array.prototype.indexOf)
{
  Array.prototype.indexOf = function(elt /*, from*/)
  {
    var len = this.length >>> 0;

    var from = Number(arguments[1]) || 0;
    from = (from < 0)
         ? Math.ceil(from)
         : Math.floor(from);
    if (from < 0)
      from += len;

    for (; from < len; from++)
    {
      if (from in this &&
          this[from] === elt)
        return from;
    }
    return -1;
  };
}

//javascript,回傳頁面後會跳至先前瀏覽卷軸
function Trim(strValue){  
    return strValue.replace(/^\s*|\s*$/g,""); 
}     
function SetCookie(sName,sValue){     
    document.cookie=sName+"="+escape(sValue);  
} 
function GetCookie(sName){   
    var aCookie = document.cookie.split(";");  
    for (var i=0; i < aCookie.length; i++){
        var aCrumb = aCookie[i].split("=");   
        if (sName == Trim(aCrumb[0])){   
              return unescape(aCrumb[1]);   
            }   
        }   
    return null;   
  }   
function scrollback(){   
  if (GetCookie("scroll")!=null){document.body.scrollTop=GetCookie("scroll")}   
} 


function funcBoardTimer(u_name){
    board_timer = setTimeout(
        function(){
            var b=new creatRequestObj();
            b.open("GET","../get_ajax.php?act=board_alt&bName="+u_name+"&"+Date.parse(new Date),!0);
            b.onreadystatechange=function(){
            if(b.readyState==4){
                var a=b.responseText;
                if(Trim(a)=="ok"){
                    alert("有最新公告,請至公告頁觀看!");    
                }
            }
        }    
        b.send(null);
        funcBoardTimer(u_name);
        },30000);
}

function get_board(act,type){
    get_board_timer = setTimeout(
        function(){
            var b=new creatRequestObj();
            b.open("GET",(type=="host"?"":"../")+"get_ajax.php?act="+act+"&type="+hu_type+"&"+Date.parse(new Date),!0);
            b.onreadystatechange=function(){
            if(b.readyState==4){
                var a=b.responseText;
                var maq_html = "<a target=\"main\" href=\"main"+(type=="host"?"":type)+".php?view_page=other_ag&sel_type=info\" style=\"color:#FFF;font-size:14px; line-height:24px;\">"+decodeURIComponent(Trim(a)).replace(/\+/ig, " ")+"</a>";
                document.getElementById("header_marquee").innerHTML = maq_html;
                
            }
        }    
        b.send(null);
        get_board(act,type);
        },60000);
}


//把隱藏的input 顯示出來
function click_edit(sno){
    $("#tr_"+sno+" span").each(function(index, element) {
        if($(this).attr("id").match(sno)){
            if($(this).css("display")=="none"){
                $(this).css("display","inline");
            }else{
                $(this).css("display","none");
            }
        }
    });
    $("#tr_"+sno+" input").each(function(index, element) {
        if($(this).attr("id").match(sno)){
            if($(this).css("display")=="none"){
                $(this).css("display","inline");
            }else{
                $(this).css("display","none");    
            }
        }
    });
    $("#tr_"+sno+" textarea").each(function(index, element) {
        if($(this).attr("id").match(sno)){
            if($(this).css("display")=="none"){
                $(this).css("display","inline");
            }else{
                $(this).css("display","none");    
            }
        }
    });    
}

//網路摳來的
//下列為自訂範圍值的亂數函式(最小值,最大值)
function rdn(min_val,max_val) {
  return Math.floor(Math.random()*(max_val-min_val+1)+min_val);
}


// 顯示讀取遮罩
function ShowProgressBar() {
    displayProgress();
    displayMaskFrame();
}
 
// 隱藏讀取遮罩
function HideProgressBar() {
    var progress = $('#divProgress');
    var maskFrame = $("#divMaskFrame");
    progress.hide();
    maskFrame.hide();
    progress.css("display","none");
    maskFrame.css("display","none");
}
// 顯示讀取畫面
function displayProgress() {
    var w = $(document).width();
    var h = $(window).height();
    h /= 2;
    w /= 2;
    var progress = $('#divProgress');
    var progress_h = (progress.height()/2);
    var progress_w = (progress.width()/2);
    //progress.css({ "z-index":999999,"top":h-progress_h,"left":w-progress_w});
    progress.css({ "z-index":999999,"top":100,"left":w-progress_w,"display":"block"});
    progress.show();
}

// 顯示遮罩畫面
function displayMaskFrame() {
    var w = $(window).width();
    var h = $(document).height();
    //var w = document.getElementById("gg").width;
    //var h = document.getElementById("gg").height;
    var maskFrame = $("#divMaskFrame");
    maskFrame.css({ "z-index": 999998, "filter": "alpha(opacity=70)","opacity":0.7, "width": w, "height": h ,"display":"block"});
    maskFrame.show();
}

/* 網路上找的,js 複製到剪貼簿功能*/
//Copy to clipboard: ref http://forum.moztw.org/viewtopic.php?p=131407

function copyToClipboard(txt) {

    var copied = false;

     if(window.clipboardData) {

        window.clipboardData.clearData();

        window.clipboardData.setData("Text", txt);

        copied = true;

     } else if (window.netscape) {

        try {

           netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");

        } catch (e) {

           alert("被瀏覽器拒絕！\n請在瀏覽器網址列輸入'about:config'\n，將'signed.applets.codebase_principal_support'設為'true'");

        }

        var clip = Components.classes['@mozilla.org/widget/clipboard;1']

        .createInstance(Components.interfaces.nsIClipboard);

        if (!clip)

           return;

        var trans = Components.classes['@mozilla.org/widget/transferable;1']

        .createInstance(Components.interfaces.nsITransferable);

        if (!trans)

           return;

        trans.addDataFlavor('text/unicode');

        var str = new Object();

        var len = new Object();

        var str = Components.classes["@mozilla.org/supports-string;1"]

        .createInstance(Components.interfaces.nsISupportsString);

        var copytext = txt;

        str.data = copytext;

        trans.setTransferData("text/unicode",str,copytext.length*2);

        var clipid = Components.interfaces.nsIClipboard;

        if (!clip)

           return false;

        clip.setData(trans,null,clipid.kGlobalClipboard);

        copied = true;

     }

     if (copied) alert('文字內容已複製到剪貼簿中!');

     else alert("使用的瀏覽器不支援文字複製功能!");

}    




