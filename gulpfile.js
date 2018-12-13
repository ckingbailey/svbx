const { src, dest, series } = require('gulp')
const fs = require('fs')
const merge = require('merge-stream')
// TODO: require { package.json }.dependencies, iterate over its keys, copying contents of various **/dist/ folders to assets/
const { dependencies } = require('./package.json')
// TODO: gulp-merge may be the plugin needed for sending multiple srcs to multiples dests

const css = () => {
    const tasks = Object.keys(dependencies).reduce((acc, dep) => {
        // TODO: check for presence of css folder in dist folder
        // TODO: check for existence of *.min.css for each file in dist/css
        // TODO: pipe the files to dest('assets/css/')
        const path = fs.existsSync(`node_modules/${dep}/dist`) && `node_modules/${dep}/dist`
        if (fs.existsSync(`${path}/css`)){
            console.log('   path = ' + path + '/css')
            acc.push(src(`${path}/css/*.css`)
                .pipe(dest('assets/css/')))
        }
        return acc
    }, [])

    return merge(tasks)
}

const js = () => {
    const tasks = Object.keys(dependencies).reduce((acc, dep) => {
        const path = fs.existsSync(`node_modules/${dep}/dist`) && `node_modules/${dep}/dist`
        if (fs.existsSync(`${path}/js`)) {
            console.log('   path = ' + path + '/js')
            acc.push(src(`${path}/js/*.js`)
                .pipe(dest('./assets/js/')))
        }
        return acc
    }, [])

    return merge(tasks)
}

exports.css = css
exports.js = js
exports.default = series(css, js)