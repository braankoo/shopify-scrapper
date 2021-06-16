var webpage = require('webpage');
var fs = require('fs');


module.exports = function (data, done, worker) {

    var params = data;

    var page = webpage.create();
    var pageId = 1;

    const writeData = function (data) {
        const path = 'data/position/' + params.hostname + '.csv';
        fs.touch(path);
        fs.write(path, data, 'a');
    }

    const loadPage = function (url, pageId) {

        page.open(url + '&page=' + pageId, function (status) {

            const productsHtml = page.evaluate(function () {
                return document.getElementById('bc-sf-filter-products').children.length;
            });

            if (productsHtml === 0) {
                loadPage(url, pageId)
            } else {
                const content = page.evaluate(function () {
                    return document.getElementById('bc-sf-filter-products').outerHTML;
                });

                const isLastPage = page.evaluate(function () {
                    return document.getElementsByClassName('paginate__link--next')[0].className.includes('--disabled');
                })

                if (isLastPage || page === 5) {
                    done(null);
                } else {
                    writeData(content);
                    loadPage(url, ++pageId)
                }
            }
        });

    }


    loadPage(data.url, pageId);
};
