function secondsToReadable(seconds) {
    if (isNaN(seconds) || seconds === 0) {
        return '';
    }

    let months = Math.floor(seconds / (3600 * 24 * 30.44)); // Average number of seconds in a month
    seconds -= months * (3600 * 24 * 30.44);

    let weeks = Math.floor(seconds / (3600 * 24 * 7));
    seconds -= weeks * (3600 * 24 * 7);

    let days = Math.floor(seconds / (3600 * 24));
    seconds -= days * (3600 * 24);

    let hours = Math.floor(seconds / 3600);
    seconds -= hours * 3600;

    let minutes = Math.floor(seconds / 60);
    seconds -= minutes * 60;

    lang = $('html').attr('lang');

    if (lang === 'ru') {
        return formatDurationRu(months, weeks, days, hours, minutes, seconds);
    } else {
        return formatDurationEn(months, weeks, days, hours, minutes, seconds);
    }
}

function formatDurationRu(months, weeks, days, hours, minutes, seconds) {
    let parts = [];
    if (months > 0) {
        parts.push(
            months + ' ' + pluralRu(months, ['месяц', 'месяца', 'месяцев']),
        );
    }
    if (weeks > 0) {
        parts.push(
            weeks + ' ' + pluralRu(weeks, ['неделя', 'недели', 'недель']),
        );
    }
    if (days > 0) {
        parts.push(days + ' ' + pluralRu(days, ['день', 'дня', 'дней']));
    }
    if (hours > 0) {
        parts.push(hours + ' ' + pluralRu(hours, ['час', 'часа', 'часов']));
    }
    if (minutes > 0) {
        parts.push(
            minutes + ' ' + pluralRu(minutes, ['минута', 'минуты', 'минут']),
        );
    }
    if (seconds > 0) {
        parts.push(
            seconds + ' ' + pluralRu(seconds, ['секунда', 'секунды', 'секунд']),
        );
    }
    return parts.join(' и ');
}

function formatDurationEn(months, weeks, days, hours, minutes, seconds) {
    let parts = [];
    if (months > 0) {
        parts.push(months + ' ' + (months === 1 ? 'month' : 'months'));
    }
    if (weeks > 0) {
        parts.push(weeks + ' ' + (weeks === 1 ? 'week' : 'weeks'));
    }
    if (days > 0) {
        parts.push(days + ' ' + (days === 1 ? 'day' : 'days'));
    }
    if (hours > 0) {
        parts.push(hours + ' ' + (hours === 1 ? 'hour' : 'hours'));
    }
    if (minutes > 0) {
        parts.push(minutes + ' ' + (minutes === 1 ? 'minute' : 'minutes'));
    }
    if (seconds > 0) {
        parts.push(seconds + ' ' + (seconds === 1 ? 'second' : 'seconds'));
    }
    return parts.join(' and ');
}

function pluralRu(n, forms) {
    return forms[
        n % 10 === 1 && n % 100 !== 11
            ? 0
            : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20)
            ? 1
            : 2
    ];
}