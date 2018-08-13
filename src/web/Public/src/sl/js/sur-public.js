var $ul = $(".sur-show-info ul"),
      size = $ul.find("li").size();
    $ul.append($ul.html());

    var $li = $ul.find('li'),
      $len = $li.size(),
      $width = $li.eq(0).width(),
      $prev = $(".sur-pre"),
      $next = $(".sur-next"),
      timer = null,
      bClick = true,
      $index = 1;

    $li.eq(1).addClass("sur-img-cur");  
  
    $ul.css("width",($width+20)*$len);  
    timer = setInterval(function(){
      $index++;
      next();
    },2000);

    $(".sur-show-wrap").on("mouseover",function(){
      clearInterval(timer);
    });

    $(".sur-show-wrap").on("mouseout",function(){
      timer = setInterval(function(){
        $index++;
        next();
      },2000);
    });

    $(document).on("click",".sur-pre",function(){
      if(!bClick) return;
      bClick = false;
      $index--;
      if($index == 0){
        
        $li.removeClass("sur-img-cur");
        $index = $len/2;
        $ul.css({left:-($index)*($width+20)});
        $li.eq($index).addClass("sur-img-cur");
        $ul.animate({left:-($index-1)*($width+20)},{complete:function(){
          bClick = true;
        }});
      } else {
        next();
      }
    });

    $(document).on("click",".sur-next",function(){
      if(!bClick) return;

      bClick = false;
      $index++;
      next();
      
    });

    function next(){
      $li.removeClass("sur-img-cur");
      // $a.removeClass("i-sur");
      if($index == $len/2+2){
        $ul.css({left:0});
        $index = 2;
        $ul.animate({left:-($index-1)*($width+20)},{complete:function(){
          bClick = true;
        }});
      } else {
        $ul.animate({left:-($index-1)*($width+20)},{complete:function(){
          bClick = true;
        }});
      }
      

      $li.eq($index).addClass("sur-img-cur");
      // $a.eq($index).addClass("i-sur");
    }

//判断是否是移动设备

// if(navigator.userAgent.match(/(iPhone|iPod|Android|ios)/i)){
//     var iScroll=0;
//     var iStartX=0;
//     var iStartPageX=0;

//     $ul.




//news


$(document).ready(function(){
    $('.sur-c li').on('click',function(){
      $(this).find('.sur-contwrap').toggle();
    })
  })



//youtube视频
      var tag = document.createElement('script');

      tag.src = "https://www.youtube.com/iframe_api";
      var firstScriptTag = document.getElementsByTagName('script')[0];
      firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    // 3. This function creates an <iframe> (and YouTube player)
      //    after the API code downloads.
      var player;
      function onYouTubeIframeAPIReady() {
        player = new YT.Player('player',{
          events: {
            'onReady': onPlayerReady
            // 'onStateChange': onPlayerStateChange
          }
        });
      }
      function clicks()
      {
        player.playVideo();
      }

      // 4. The API will call this function when the video player is ready.
      function onPlayerReady(event) {
        event.target.playVideo();
      }
      var done = false;
      function onPlayerStateChange(event) {
        console.log(event)
        if (event.data == YT.PlayerState.PLAYING && !done) {
          setTimeout(stopVideo, 6000);
          done = true;
        }
      }
      function stopVideo() {
        player2.stopVideo();
    }

