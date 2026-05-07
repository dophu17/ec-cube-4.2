function remise_waitscreen() {
    $('#dialog-area').appendTo('body');
    var maskHeight = $(document).height();
    var maskWidth = $(document).width();
    var dialogTop = ($(window).height()/2) - ($('#dialog-box').height());
    var dialogLeft = (maskWidth/2) - ($('#dialog-box').width()/2);
    $('#dialog-overlay').css({height:maskHeight, width:maskWidth, zIndex:9998}).show();
    $('#dialog-box').css({top:dialogTop, left:dialogLeft, zIndex:9999}).show();
    $('#dialog-message').html();
    scroll(0,0);
}
