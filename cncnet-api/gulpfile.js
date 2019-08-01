'use strict';

const gulp = require("gulp");
const sass = require("gulp-sass");
const autoprefixer = require("gulp-autoprefixer");

// Watch files
function watchFiles() 
{
    gulp.watch(["./resources/sass/**/*"], css);
}

function css()
{
    return gulp
        .src("./resources/sass/ladder.scss")
        .pipe(sass({
            outputStyle: "compressed"
        }).on("error", sass.logError))
        .pipe(autoprefixer())
        .pipe(gulp.dest("./public/css/"));
}

const watch = gulp.parallel(css, watchFiles);

exports.default = watch;