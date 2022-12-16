let { gulp, watch, series, src, dest } = require('gulp');
let sass = require('gulp-sass')(require('sass'));

function compileSassFiles(done)
{
    src("./resources/stylesheets/*.scss")

        // Apply sass on the files found
        .pipe(sass())

        // Log errors from the sass
        .on("error", sass.logError)

        // Destination for the compiled file(s)
        .pipe(dest("./public/css"));

    done();
}

exports.compile = function (done)
{
    watch(
        'resources/stylesheets/**/*.scss',
        {
            ignoreInitial: false
        },
        compileSassFiles
    );
}