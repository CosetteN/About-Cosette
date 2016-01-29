casper.test.begin('Contact Page', 1, function suite(test){

    /* Start the test. Check the title is as expected */
    casper.start("http://www.cosettenewberry.com/contact", function(){
        test.assertTitle("Cosette Newberry", "Title of my page is my name.")
    });

    /* Complete Test */
    casper.run(function(){
        test.done();
    });
});
