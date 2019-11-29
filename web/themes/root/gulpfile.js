var gulp = require('gulp'),
	sass = require('gulp-sass'),
	autoprefixer = require('gulp-autoprefixer'),
	sourcemaps = require('gulp-sourcemaps'),
	del = require('del');

gulp.task('sass', function () {
    gulp.src(['./assets/scss/root.scss', './assets/scss/root-rtl.scss', './assets/scss/content-form.scss'])
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer('last 2 version'))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./assets/css'));
});

gulp.task('build', function(){
	gulp.start(['sass']);
});

gulp.task('watch', ['sass'], function(){
    gulp.watch('./assets/scss/**/*.scss', ['sass']);
});
