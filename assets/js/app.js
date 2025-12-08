// Small helper JS - keep minimal and progressive enhancement friendly
document.addEventListener('DOMContentLoaded', function(){
    // simple behavior: add confirm on logout link
    var logout = document.querySelector('.btn-logout');
    if(logout){
        logout.addEventListener('click', function(e){
            if(!confirm('ออกจากระบบจริงหรือไม่?')) e.preventDefault();
        });
    }
});
