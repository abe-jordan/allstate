<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    <?php if(session('status')): ?>
                        <div class="alert alert-success">
                            <?php echo e(session('status')); ?>

                        </div>
                    <?php endif; ?>
<form method="POST" action="/download">
                        <?php echo csrf_field(); ?>

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right"><?php echo e(__('File')); ?></label>

                            <div class="col-md-6">
                                <select id="name" type="text" class="form-control<?php echo e($errors->has('name') ? ' is-invalid' : ''); ?>" name="export_type" value="<?php echo e(old('name')); ?>" required autofocus>
                                    <option value="STQUOTES">STQUOTES</option>
                                    <option value="STINVOICES">STINVOICES</option>
                                    <option value="UNSCHEDULED_APPOINTMENTS">Unscheduled jobs with quotes</option>
                                </select>
                                <?php if($errors->has('name')): ?>
                                    <span class="invalid-feedback">
                                        <strong><?php echo e($errors->first('name')); ?></strong>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>





                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <?php echo e(__('Export Data')); ?>

                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<script>
console.log(document.getElementById('name'));
var curr = new Date; // get current date
var first = curr.getDate() - curr.getDay(); // First day is the day of the month - the day of the week
var last = first + 6; // last day is the first day + 6

var firstday = new Date(curr.setDate(first));
var begin = firstday.getFullYear() + '-' + ('0' + (firstday.getMonth()+1)).slice(-2) + '-' + firstday.getDate() ;
console.log(begin);

var lastday = new Date(curr.setDate(last)).toUTCString();
console.log(firstday); // Mon Nov 08 2010
console.log(lastday); // Mon Nov 08 2010
</script>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>