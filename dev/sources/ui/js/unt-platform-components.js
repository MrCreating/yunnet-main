(function () {
    unt.changeWindowTo = function (windowType) {
        if (windowType === 1) {
            document.getElementsByClassName('main-interface-window')[0].style.display = 'flex';
            document.getElementsByClassName('main-information-window')[0].style.display = 'none';
        }
        if (windowType === 0) {
            document.getElementsByClassName('main-interface-window')[0].style.display = 'none';
            document.getElementsByClassName('main-information-window')[0].style.display = 'flex';
        }
    }

    unt.request = function (url = window.location.href, type = 'GET', data = null) {
        return new Promise(function (resolve, reject) {
           let x = _xmlHttpGet();

           if (data !== null) {
               if (!(data instanceof FormData)) {
                   let result = new FormData();

                   for (let key in data) {
                       result.append(key, data[key]);
                   }

                   data = result;
               }
           }

           x.onreadystatechange = function () {
               if (x.readyState !== 4) return;

               let response = x.responseText;
               if (x.status === 200) {
                   return resolve(response);
               } else {
                   return reject(x.status);
               }
           }

           x.withCredentials = true;

           x.open(type, url);

           if (type !== 'POST')
               x.send();
           else
               x.send(data);
        });
    }

    unt.request.get = function (url) {
        return unt.request(url, 'GET');
    }
    unt.request.post = function (url, data) {
        return unt.request(url, 'POST', data);
    }
})(unt);