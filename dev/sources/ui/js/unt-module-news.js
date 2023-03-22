(function (unt) {
    unt.modules.news = {
        init: function () {
            unt.changeWindowTo(1);

            document.title = unt.users.lang.news;
        }
    };

    unt.components.time = function (timestamp, withOutDate = false, withOutHours = false) {
        if (withOutHours && withOutDate) {
            withOutDate = !withOutDate;
        }

        if (!timestamp) return "";
        let time = new Date(Number(timestamp) * 1000);

        let fullYear = String(time.getFullYear());
        let fullMonth = ((time.getMonth() + 1) < 10) ? ("0" + String(time.getMonth() + 1)) : (String(time.getMonth() + 1));
        let fullDay = ((time.getDate()) < 10) ? ("0" + String(time.getDate())) : (String(time.getDate()));

        let fullHours = ((time.getHours()) < 10) ? ("0" + String(time.getHours())) : (String(time.getHours()));
        let fullMinutes =  ((time.getMinutes()) < 10) ? ("0" + String(time.getMinutes())) : (String(time.getMinutes()));

        let resultedString = '';
        if (!withOutDate) resultedString += (fullDay + '.' + fullMonth + '.' + fullYear) + ' ';
        if (!withOutHours) resultedString += (fullHours + ':' + fullMinutes);

        return resultedString;
    }

    unt.components.post = function (post) {
        let element = document.createElement('div');
        element.boundPost = post;
        element.classList.add('card');
        element.style.padding = '15px';
        element.style.margin = '0';
        element.style.marginTop = '3px';

        element.innerHTML = `
            <div>
                <div class="valign-wrapper">
                    <div>
                        <img src="" class="circle post_author_photo" height="32" width="32">
                    </div>
                    <div style="width: 100%; margin-left: 10px" class="item-halign-wrapper">
                        <div><b><a target="_blank" style="color: var(--unt-links-color, black)" class="post_author_credentials">...</a></b></div>
                        <div style="font-size: 10px">${unt.components.time(post.time)}</div>
                    </div>
                    <div>
                        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 96 960 960" width="24"><path d="M479.858 896Q460 896 446 881.858q-14-14.141-14-34Q432 828 446.142 814q14.141-14 34-14Q500 800 514 814.142q14 14.141 14 34Q528 868 513.858 882q-14.141 14-34 14Zm0-272Q460 624 446 609.858q-14-14.141-14-34Q432 556 446.142 542q14.141-14 34-14Q500 528 514 542.142q14 14.141 14 34Q528 596 513.858 610q-14.141 14-34 14Zm0-272Q460 352 446 337.858q-14-14.141-14-34Q432 284 446.142 270q14.141-14 34-14Q500 256 514 270.142q14 14.141 14 34Q528 324 513.858 338q-14.141 14-34 14Z"/></svg>
                    </div>
                </div>
            </div>
            <div class="post_text"></div>
            <div class="post_attachments"></div>
            <div class="post_actions_with_post valign-wrapper" style="margin-top: 5px">
                <div class="valign-wrapper">
                    <div class="icon_liked">
                    </div>
                    <div style="margin-left: 5px">${post.likes || 0}</div>
                </div>
                <div class="valign-wrapper">
                    <div style="margin-left: 10px">
                        <svg style="margin-bottom: -7px" xmlns="http://www.w3.org/2000/svg" height="24" width="24" viewBox="0 96 960 960"><path d="M240 656h480v-60H240v60Zm0-130h480v-60H240v60Zm0-130h480v-60H240v60Zm640 580L720 816H140q-23 0-41.5-18.5T80 756V236q0-23 18.5-41.5T140 176h680q24 0 42 18.5t18 41.5v740ZM140 236v520h605l75 75V236H140Zm0 0v595-595Z"/></svg>
                    </div>
                    <div style="margin-left: 5px">${post.comments.count || 0}</div>
                </div>
            </div>
        `;

        element.getElementsByClassName('post_text')[0].innerHTML = nl2br(htmlspecialchars(post.text).linkify());

        let svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        let path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

        if (post.like_me) {
            path.setAttribute('d', "m12 21.35-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z");
            svg.setAttribute('viewBox', "0 0 24 24");
            path.setAttribute('fill', 'red');
        } else {
            path.setAttribute('d', "M16.5 3c-1.74 0-3.41.81-4.5 2.09C10.91 3.81 9.24 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.32C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.55-.1.1-.1-.1C7.14 14.24 4 11.39 4 8.5 4 6.5 5.5 5 7.5 5c1.54 0 3.04.99 3.57 2.36h1.87C13.46 5.99 14.96 5 16.5 5c2 0 3.5 1.5 3.5 3.5 0 2.89-3.14 5.74-7.9 10.05z");
            svg.setAttribute('viewBox', "0 0 24 24");
        }
        svg.appendChild(path);
        svg.setAttribute('width', '24');
        svg.setAttribute('height', '24');
        svg.style.marginBottom = '-5px';

        element.getElementsByClassName('icon_liked')[0].appendChild(svg);

        unt.users.get(post.owner_id).then(function (user) {
            element.getElementsByClassName('post_author_credentials')[0].href = '/' + (user.screen_name || (user.account_type === 'user' ? ('id' + user.user_id) : ('bot' + user.bot_id)))
            element.getElementsByClassName('post_author_credentials')[0].innerText = (user.name || (user.first_name + ' ' + user.last_name));
            element.getElementsByClassName('post_author_photo')[0].src = user.photo_url;
        }).catch(function (err) {
            element.getElementsByClassName('post_author_credentials')[0].innerText = unt.users.lang.deleted_account;
        });

        console.log(post);

        return element;
    }
})(unt);