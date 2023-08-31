 <a href="https://t.me/brokendeejay_ipl">
         <img alt="Qries" src="https://mechktuassist.in/wp-content/uploads/2020/10/banner3.png"
         width=100%" height="200">
<html>
<meta content='width=device-width, initial-scale=1, maximum-scale=1' name='viewport'/>

<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="referrer" content="no-referrer">
<meta charset="utf-8">
<script type="text/javascript" src="https://cdn.jsdelivr.net/clappr/latest/clappr.min.js"></script>
<script type="text/javascript" src="//cdn.jsdelivr.net/gh/clappr/clappr-level-selector-plugin@latest/dist/level-selector.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<script type="text/javascript" src="https://cdn.jsdelivr.net/clappr.rtmp/latest/rtmp.min.js"></script>
<script src="//cdn.jsdelivr.net/npm/cdnbye@latest/dist/hlsjs-p2p-engine.min.js" type="text/javascript"> </script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/clappr.chromecast-plugin/latest/clappr-chromecast-plugin.min.js"></script>


<script src="https://cdn.plyr.io/3.6.2/plyr.polyfilled.js"></script>
<title>Follow Us</title>


</head>
<body>


</div>
<div style="width: 100%;">

<div id="player"></div>
</div>
<script>
function getParam ( sname )
{
  var params = location.search.substr(location.search.indexOf("?")+1);
  var sval =  params.replace("sv=", "");
  
  return sval;
}
var sv = getParam("sv");

</script>
<script>
  var responseText = ''+sv+'';
  urlArry = responseText.split(',');
  start = true;
  num_of_urlArry = urlArry.length;
  index_of_urlArry = 0;
</script>
<script>
 if (navigator.userAgent.match(/Android/i) ||
             navigator.userAgent.match(/webOS/i) ||
             navigator.userAgent.match(/iPhone/i) ||
             navigator.userAgent.match(/iPod/i) ||
             navigator.userAgent.match(/iPad/i) ||
             navigator.userAgent.match(/Blackberry/i)){
    document.write("\<video style=\"z-index:2;width:100%;height:250;background-color:#000;-o-object-fit:fill;object-fit:fill\"  controls=\"controls\" autoplay=\"true\" preload=\"auto\" height=\"auto\" src=\""+urlArry[0]+"\"\>\<\/video\>");
    }else{
	player = new Clappr.Player({
								source: ""+sv+"",
                                                     parentId: '#player',
                                               
                                                    width: '100%',
                                                    height: "100%",
					            hideMediaControl: true,
					            seekbar: "#ffaa56",
					            buttons: "#FFFF00",gaAccount: 'UA-128386009-2',gaTrackerName: 'hackiesite',
					            autoPlay: 'true',
					            hide: 'false',
					            watermark: "https://i.imgur.com/5xLFkDA.png?1", position: 'bottom-right', 
                                                   watermarkLink: "https://telegram.me/joinchat/UfaDu9ueYiR-W8Eg",position: "top-right",

      events: {
       onError : function (event) {
        if(start == true)
        {
         index_of_urlArry = index_of_urlArry + 1;
         
         if(index_of_urlArry <= num_of_urlArry){
          reLoad(urlArry[index_of_urlArry]);
         }

 } else{ reLoad(urlArry[index_of_urlArry]);}},
       onBuffer: function (event){
        playing = false;
        setTimeout(function(){
         if(playing == false){
          if(start == true){
           index_of_urlArry = index_of_urlArry + 1;
         
           if(index_of_urlArry <= num_of_urlArry){
            reLoad(urlArry[index_of_urlArry]);
           }
          }else{
           reLoad(urlArry[index_of_urlArry]);
          }
         }
        },20000);
        
       },
       onPlay: function (event){
        start = false;
        playing = true;
        
       }
      }
     });
}
	</script>
</body>
</html>
 <a href="https://t.me/brokendeejay_ipl">
         <img alt="Qries" src="https://mechktuassist.in/wp-content/uploads/2020/10/banner3.png"
         width=100%" height="200">
