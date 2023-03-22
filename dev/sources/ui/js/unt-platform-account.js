(function (unt) {
    unt.users = {
        list: {},
        lang: null,
        getLanguage: function () {
            return new Promise(function (resolve, reject) {
                return unt.request.post('/flex', {
                    action: 'get_language_value',
                    value: '*'
                }).then(function (response) {
                    try {
                        response = JSON.parse(String(response));

                        return resolve(unt.users.lang = response);
                    } catch (e) {
                        return reject(null);
                    }
                }).catch(function () {
                    return reject(null);
                })
            })
        },
        get: function (user_id = 0, fields = '*') {
            return new Promise(function (resolve, reject) {
                if (unt.users[user_id])
                    return resolve(unt.users[user_id]);

                return unt.request.post('/flex', {
                    action: 'get_user_data',
                    id: user_id,
                    fields: fields || '*'
                }).then(function (response) {
                    try {
                        response = JSON.parse(String(response));

                        if (response.error)
                            return reject(new ReferenceError('User not found'));

                        return resolve(unt.users[user_id] = response.response);
                    } catch (e) {
                        return reject(new Error('Data parse failed.'));
                    }
                }).catch(function (errCode) {
                    return reject(new Error('Sent request failed'));
                })
            });
        }
    };
})(unt);