import { existsSync, readdirSync, statSync } from 'node:fs'
import { join, resolve } from 'node:path'

const INVUE_IMPORT = /^invue\/([^/]+)(\/.*)?$/

function discoverPackages(vendorDir) {
    const invueDir = join(vendorDir, 'invue')
    const packages = {}

    if (!existsSync(invueDir)) {
        return packages
    }

    for (const name of readdirSync(invueDir)) {
        const packageDir = join(invueDir, name)
        const jsDir = join(packageDir, 'resources', 'js')

        if (statSync(packageDir).isDirectory() && existsSync(jsDir)) {
            packages[name] = jsDir
        }
    }

    return packages
}

export default function invue(options = {}) {
    let packages = {}

    return {
        name: 'invue',
        enforce: 'pre',
        configResolved(config) {
            const vendorDir = options.vendorPath ?? resolve(config.root, 'vendor')
            packages = discoverPackages(vendorDir)
        },
        resolveId(source) {
            const match = source.match(INVUE_IMPORT)

            if (!match) {
                return null
            }

            const [, name, subpath] = match
            const jsDir = packages[name]

            if (!jsDir) {
                return null
            }

            return subpath ? join(jsDir, subpath) : join(jsDir, 'index.js')
        },
    }
}
