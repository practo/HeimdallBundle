from fabpolish import polish, sniff, info, local
from fabpolish.contrib import find_merge_conflict_leftovers


@sniff(severity='major', timing='fast')
def fix_file_permission():
    """Fixing permissions for files"""
    info('Fixing permissions for files')
    return local(
        "git ls-files -z | "
        "grep -PvZz '\.sh$' | "
        "xargs -0 chmod -c 0664 > /dev/null 2>&1"
    )


@sniff(severity='major', timing='fast')
def fix_script_permission():
    # Fix script permissions
    info('Fixing script permissions...')
    return local(
        "git ls-files -z | "
        "grep -PZz '\.sh$' | "
        "xargs -0 -r chmod 0775 >/dev/null 2>&1"
    )


@sniff(severity='major', timing='fast')
def fix_directory_permission():
   info('Fixing directory permissions...')
   return local(
       '! find . '
       '-path ./.git -prune -o '  # Exclude .git directory
       '-path ./vendor -prune -o '  # Exclude vendor directory
       '-type d -not -perm 0775 -print0 | '
       'tee /dev/stderr | '
       'xargs -0 chmod 0775 >/dev/null 2>&1'
   )


@sniff(severity='major', timing='fast')
def fix_white_space():
    info('Fixing whitespace errors...')
    return local(
        "git ls-files -z | "
        "grep -PZvz '\.(ico|jpg|png|gif|eot|ttf|woff|wav)$' | "
        "xargs -0 grep -PlZn '(\\s+$)|(\\t)' | "
        "tee /dev/stderr | "
        "xargs -0 -r sed -i -e 's/\\s\\+$//' "
    )


@sniff(severity='major', timing='fast')
def convert_tab_spaces():
    info('Converting tab to spaces...')
    return local(
        "git ls-files -z | "
        "grep -PZvz '\.(ico|jpg|png|gif|eot|ttf|woff|wav)$' | "
        "xargs -0 grep -PlZn '(\\s+$)|(\\t)' | "
        "tee /dev/stderr | "
        "xargs -0 -r sed -i -e 's/\\t/    /g' "
    )


@sniff(severity='major', timing='fast')
def php_coding_check():
    # Run coding standards check
    info('Running coding standards check...')
    return local('phpcs --standard=Symfony2 . --ignore=genphp,vendor')


@sniff(severity='major', timing='fast')
def check_var_dump():
    info('Checking for var_dump, echo or die statements...')
    #return local("! git grep 'var_dump'")
    return local(
        "! git ls-files -z | "
        "grep -PZz '\.php$' | "
        "xargs -0 egrep -n 'var_dump|echo|die' | grep -v 'NOCHECK'"
    )
