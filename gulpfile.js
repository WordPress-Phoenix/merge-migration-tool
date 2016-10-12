// plugins
var gulp = require('gulp');
var sass = require('gulp-sass');
var minifycss = require('gulp-uglifycss');
var autoprefixer = require('gulp-autoprefixer');
var mmq = require('gulp-merge-media-queries');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var imagemin = require('gulp-imagemin')
var rename = require('gulp-rename');
var lineec = require('gulp-line-ending-corrector');
var filter = require('gulp-filter');
var sourcemaps = require('gulp-sourcemaps');
var notify = require('gulp-notify');

// paths
var paths = {
    jsSrc: ['./assets/js/src/mmt.js'],
    jsDest: './assets/js/',
    jsWatchers: ['./assets/js/src/*.js'],
    cssSrc: ['./assets/css/scss/mmt.scss'],
    cssDest: './assets/css/',
    cssSourceMapDest: './',
    cssWatchers: ['./assets/css/scss/**/*.scss'],
    imgSrc: ['./assets/img/**/*.{png,jpg,gif,svg}'],
    imgDest: './assets/img/'
};

// autoprefixer
const AUTOPREFIXER_BROWSERS = [
    'last 2 version',
    '> 1%',
    'ie >= 9',
    'ie_mob >= 10',
    'ff >= 30',
    'chrome >= 34',
    'safari >= 7',
    'opera >= 23',
    'ios >= 7',
    'android >= 4',
    'bb >= 10'
];

// css
gulp.task('css', function () {
    gulp.src(paths.cssSrc)
        .pipe(sourcemaps.init())
        .pipe(sass({
            errLogToConsole: true,
            outputStyle: 'compact',
            //outputStyle: 'compressed',
            // outputStyle: 'nested',
            // outputStyle: 'expanded',
            precision: 10
        }))
        .on('error', console.error.bind(console))
        .pipe(sourcemaps.write({includeContent: false}))
        .pipe(sourcemaps.init({loadMaps: true}))
        .pipe(autoprefixer(AUTOPREFIXER_BROWSERS))
        .pipe(sourcemaps.write(paths.cssSourceMapDest))
        .pipe(lineec()) // Consistent Line Endings for non UNIX systems.
        .pipe(gulp.dest(paths.cssDest))
        .pipe(filter('**/*.css')) // Filtering stream to only css files
        .pipe(mmq({log: true})) // Merge Media Queries only for .min.css version.
        .pipe(rename({suffix: '.min'}))
        .pipe(minifycss({
            maxLineLen: 10
        }))
        .pipe(lineec()) // Consistent Line Endings for non UNIX systems.
        .pipe(gulp.dest(paths.cssDest))
        .pipe(filter('**/*.css')) // Filtering stream to only css files
        .pipe(notify({message: 'TASK: "css" Completed!', onLast: true}))
});

// javascript
gulp.task('javascript', function () {
    gulp.src(paths.jsSrc)
        .pipe(concat('mmt.js'))
        .pipe(lineec()) // Consistent Line Endings for non UNIX systems.
        .pipe(gulp.dest(paths.jsDest))
        .pipe(rename({
            basename: 'mmt',
            suffix: '.min'
        }))
        .pipe(uglify())
        .pipe(lineec()) // Consistent Line Endings for non UNIX systems.
        .pipe(gulp.dest(paths.jsDest))
        .pipe(notify({message: 'TASK: "javascript" Completed!', onLast: true}));
});

// images
gulp.task('images', function () {
    gulp.src(paths.imgSrc)
        .pipe(imagemin({
            progressive: true,
            optimizationLevel: 3, // 0-7 low-high
            interlaced: true,
            svgoPlugins: [{removeViewBox: false}]
        }))
        .pipe(gulp.dest(paths.imgDest))
        .pipe(notify({message: 'TASK: "images" Completed!', onLast: true}));
});

// watch task
gulp.task('watch', function () {
    gulp.watch(paths.cssWatchers, ['css']);
    gulp.watch(paths.jsWatchers, ['javascript']);
});

// default task
gulp.task('default', ['css', 'javascript']);