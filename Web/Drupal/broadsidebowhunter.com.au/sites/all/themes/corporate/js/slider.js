jQuery(function(){
  jQuery('#slides').slides({
    play: 6000, 
    hoverPause: false, 
    effect: 'fade',
    fadeSpeed: 3000,
    crossfade: true,
    randomize: true
  });
});

jQuery(document).ready(function() {
  jQuery("#slides").hover(function() {
    jQuery(".slides_nav").css("display", "block");
  },
  function() {
    jQuery(".slides_nav").css("display", "none");
  });
}); 
