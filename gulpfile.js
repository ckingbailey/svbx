const { src, dest, series } = require('gulp')
const fs = require('fs')
const merge = require('merge-stream')
const { dependencies } = require('./package.json')

const css = () => {
    const destPath = (process.env.NODE_ENV === 'prod' ? 'public_html/' : '')
        + 'assets/css/'

    const tasks = Object.keys(dependencies).reduce((acc, dep) => {
        // TODO: check for presence of css folder in dist folder
        // TODO: check for existence of *.min.css for each file in dist/css
        // TODO: pipe the files to dest('assets/css/')
        const srcPath = fs.existsSync(`node_modules/${dep}/dist`) && `node_modules/${dep}/dist`
        if (fs.existsSync(`${srcPath}/css`)){
            console.log('   srcPath = ' + srcPath + '/css')
            acc.push(src(`${srcPath}/css/*.css`)
                .pipe(dest(destPath)))
        }
        return acc
    }, [])

    return merge(tasks)
}

const js = () => {
    const destPath = (process.env.NODE_ENV === 'prod' ? 'public_html/' : '')
        + 'assets/js/'

    const tasks = Object.keys(dependencies).reduce((acc, dep) => {
        const srcPath = fs.existsSync(`node_modules/${dep}/dist`) && `node_modules/${dep}/dist`
        if (fs.existsSync(`${srcPath}/js`)) {
            console.log('   srcPath = ' + srcPath + '/js')
            acc.push(src(`${srcPath}/js/*.js`)
                .pipe(dest(destPath)))
        }
        return acc
    }, [])

    return merge(tasks)
}

exports.css = css
exports.js = js
exports.default = series(css, js)