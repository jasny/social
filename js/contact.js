$(document).ready(function() {   
    $('#submit').click(function () {       
         
        var name = $('input[name=name]');
        var email = $('input[name=email]');
        var subject = $('input[name=subject]');
        var message = $('textarea[name=message]');
 
        if (name.val()=='') {
            name.addClass('highlight');
            return false;
        } else name.removeClass('highlight');
         
        if (email.val()=='') {
            email.addClass('highlight');
            return false;
        } else email.removeClass('highlight');
         
        if (subject.val()=='') {
            subject.addClass('highlight');
            return false;
        } else subject.removeClass('highlight');
        
        if (message.val()=='') {
            message.addClass('highlight');
            return false;
        } else message.removeClass('highlight');
         
        var data = 'name=' + name.val() + '&email=' + email.val() + '&subject=' + subject.val() + '&message=' + message.val();
                 
        //start the ajax
        $.ajax({
            //this is the php file that processes the data and send mail
            url: "process.php",
             
            //GET method is used
            type: "GET",
 
            //pass the data        
            data: data,    
             
            //Do not cache the page
            cache: false,
             
            //success
            success: function (html) {             
                //if process.php returned 1/true (send mail success)
                if (html==1) {                 
                    //hide the form
                    $('#cform').fadeOut('slow');                
                     
                    //show the success message
                    $('#done').fadeIn('slow');
                     
                //if process.php returned 0/false (send mail failed)
                } else alert('Sorry, unexpected error. Please try again later.');              
            }      
        });
         
        //cancel the submit button default behaviours
        return false;
    });
}); 