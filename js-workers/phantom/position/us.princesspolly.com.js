var webpage = require('webpage');
var fs = require('fs');


module.exports = function (data, done, worker) {

    var params = data;

    var page = webpage.create();
    page.settings.loadImages = false;
    var pageId = 1;
    var fail = 0;

    const writeData = function (data) {
        fs.write(params.filePath, data, 'a');
    }

    const loadPage = function (url, pageId) {

        page.open(url + '&page=' + pageId, function (status) {

            if (status !== 'success') {
                fail++;

                setTimeout(function () {
                    loadPage(url, pageId);
                }, 30000);
                return;

            }

            const isLastPage = page.evaluate(function () {
                return document.getElementsByClassName('paginate__link--next')[0].className.includes('--disabled');
            });

            if (isLastPage) {
                done(null);
            }

            if (fail === 3000) {
                done(null);
            }

            if (pageId === 30) {
                done(null);
            }


            const productsHtml = page.evaluate(function () {
                return document.getElementById('bc-sf-filter-products').children.length;
            });


            if (productsHtml === 0) {
                fail++;
                loadPage(url, pageId);


            } else {
                const content = page.evaluate(function () {
                    return document.getElementById('bc-sf-filter-products').outerHTML;
                });


                const toWrite = content.match(/data-product-selected-variant=".\d*/g);
                writeData(toWrite.toString());
                loadPage(url, ++pageId);


            }

        });

    }


    loadPage(data.url, pageId);
};
