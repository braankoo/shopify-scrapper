var webpage = require('webpage');
var fs = require('fs');


module.exports = function (data, done, worker) {

    var params = data;

    var page = webpage.create();
    const writeData = function (data) {
        const path = 'data/quantity/' + params.hostname + '.csv';
        fs.touch(path);
        fs.write(path, data, 'a');
    }
    page.open(params.url, function (status) {
        const content = page.content;
        writeData(content);
    });


}
;
