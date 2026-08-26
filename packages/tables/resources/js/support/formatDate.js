const pad = (n) => String(n).padStart(2, '0')

export function formatDate(value, pattern) {
    const date = value instanceof Date ? value : new Date(value)

    if (Number.isNaN(date.getTime())) {
        return String(value)
    }

    const tokens = {
        YYYY: date.getFullYear(),
        MM: pad(date.getMonth() + 1),
        DD: pad(date.getDate()),
        HH: pad(date.getHours()),
        mm: pad(date.getMinutes()),
        ss: pad(date.getSeconds()),
    }

    return pattern.replace(/YYYY|MM|DD|HH|mm|ss/g, (token) => tokens[token])
}

export function timeAgo(value) {
    const date = value instanceof Date ? value : new Date(value)

    if (Number.isNaN(date.getTime())) {
        return String(value)
    }

    const seconds = Math.round((Date.now() - date.getTime()) / 1000)
    const divisions = [
        [60, 'second'],
        [60, 'minute'],
        [24, 'hour'],
        [7, 'day'],
        [4.34524, 'week'],
        [12, 'month'],
        [Number.POSITIVE_INFINITY, 'year'],
    ]

    let duration = seconds

    for (const [amount, unit] of divisions) {
        if (Math.abs(duration) < amount) {
            const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' })

            return rtf.format(-Math.round(duration), unit)
        }

        duration /= amount
    }

    return String(value)
}
