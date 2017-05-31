const gulp = require('gulp');
const babel = require('gulp-babel');
const sass = require('gulp-sass');
const php = require('gulp-connect-php');
const autoprefixer = require('gulp-autoprefixer');
const sourcemaps = require('gulp-sourcemaps');
const browserSync = require('browser-sync');
const useref = require('gulp-useref');
const uglify = require('gulp-uglify');
const htmlmin = require('gulp-htmlmin');
const gulpIf = require('gulp-if');
const cssnano = require('gulp-cssnano');
const imagemin = require('gulp-imagemin');
const cache = require('gulp-cache');
const changed = require('gulp-changed');
const del = require('del');
const runSequence = require('run-sequence');

/***
 *
 *  Development tasks
 *
 ***/

// Start browserSync server
gulp.task('php', function() {
    php.server({ base: 'app/web', port: 8888, keepalive: true});
});

gulp.task('browserSync',['php'], function() {
    browserSync({
        proxy: '127.0.0.1:8888',
        port: 8888,
        open: true,
        notify: false
    });
});

gulp.task('sass', function () {
    return gulp.src('app/web/scss/**/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass().on('error', sass.logError))
        .pipe(autoprefixer({
            browsers: ['last 4 versions', '>1%']
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('app/web/css'))
        .pipe(browserSync.reload({
            stream: true
        }))
});

// Watchers
gulp.task('watch', function () {
    gulp.watch('app/web/scss/**/*.scss', ['sass']);
    gulp.watch('app/web/*.html', browserSync.reload);
    gulp.watch('app/web/*.php', browserSync.reload);
    gulp.watch('app/templates/**/*.html.twig', browserSync.reload);
    gulp.watch('app/src/**/*.php', browserSync.reload);
    gulp.watch('app/translations/**/*.xlf', browserSync.reload);
    gulp.watch('app/web/js/**/*.js', browserSync.reload)
});

/***
 *
 *  Optimization tasks
 *
 ***/

// Optimizing CSS and JavaScript
gulp.task('useref', function () {

    return gulp.src('app/web/*.html')
        .pipe(useref())
        .pipe(changed('dist/web'))
        .pipe(gulpIf('*.html', htmlmin({collapseWhitespace: true})))
        .pipe(gulpIf('*.js', babel({
            presets: ['env']
        })))
        .pipe(gulpIf('*.js', uglify()))
        .pipe(gulpIf('*.css', cssnano()))
        .pipe(gulp.dest('dist/web'))
});

// Optimizing images
gulp.task('images', function () {
    return gulp.src('app/web/img/**/*.+(png|jpg|jpeg|gif|svg)')
    // Caching images that ran through imagemin
        .pipe(cache(imagemin({progressive: true})))
        .pipe(gulp.dest('dist/web/img'))
});

// Copying fonts
gulp.task('fonts', function () {
    return gulp.src('app/web/fonts/**/*')
        .pipe(gulp.dest('dist/web/fonts'))
});

// Cleaning
gulp.task('clean', function () {
    return del.sync('dist/web').then(function (cb) {
        return cache.clearAll(cb)
    })
});

gulp.task('clean:dist/web', function () {
    return del.sync(['dist/web/**/*', '!dist/web/img', '!dist/web/img/**/*'])
});

/***
 *
 *  Build sequences
 *
 ***/

gulp.task('default', function (callback) {
    runSequence(['sass', 'browserSync'], 'watch',
        callback
    )
});

gulp.task('build', function (callback) {
    runSequence(
        'clean:dist/web',
        'sass',
        ['useref', 'images', 'fonts'],
        callback
    )
});