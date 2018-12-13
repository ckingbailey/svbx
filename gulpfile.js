const { src, dest, series } = require('gulp')
const { dependencies } = require('./package.json');

const css = () => {
    return src('./node_modules/bootstrap-multiselect/dist/css/*')
        .pipe(dest('./assets/css/'))
}

const js = () => {
    return src('./node_modules/bootstrap-multiselect/dist/js/*')
        .pipe(dest('./assets/js/'))
}

exports.default = series(css, js)