var webpage = require('webpage');
var fs = require('fs');


// worker needs to export one function which is called with the job
module.exports = function (data, done, worker) {

    var params = data;
// Creates an empty file

    // data contains the data we passed to the job function in the master file
    // done is a function which needs to be called to signal that the job is executed
    // worker contains some meta data about this worker (like the id)

    // we just fetch the page and save it as an image normally
    var page = webpage.create();
    page.open(data.url, function () {
        const path = 'data/' + params.productId + '.csv';
        fs.touch(path);
        fs.write(path, page.content, 'a');
        
        // then we call the done function with null to signal we sucessfully executed the job
        done(null);
    });

};
