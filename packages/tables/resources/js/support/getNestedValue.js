export function getNestedValue(row, path) {
    if (!row || !path) {
        return undefined
    }

    return path.split('.').reduce((value, key) => (value == null ? undefined : value[key]), row)
}
