<style>
    .table td, .table th {
        white-space: wrap;
    }
</style>
<div class="col-xl-12">
    <div class="card">
        <div class="card-header">
            <h5>
                Translations (Count: <?= $count; ?>)
            </h5>



            <div class="card-header-right">

                <div class="input-group">


                    <?= $this->Html->link($translate->word('Reset'), [
                        'action'=>'index',"?"=>['reset'=>true]
                    ],['class'=>'btn btn-primary']); ?>
                </div>


            </div>

        </div>
        <!-- import -->
        <div class="card-header" id = "translations-import" style="display: none;">
            <div class="row">
                <div class="col-lg-8"></div>
            </div>
        </div>



            <div class="card-block table-border-style">
                <?= $this->Form->create($entity, ['id'=>'translations-form']); ?>
                <div class="row">
                    <div class="col-lg-4 text-center text-info mb-1">
                        Missing keywords ? Contact Support and we will modify source files to connect with these translations
                    </div>
                    <div class="col-lg-4">
                        <?= $this->Form->control('search',[
                            'class'=>'form-control',
                            'onkeyup'=>'searchTranslation()',
                            'label'=>false,
                            'placeholder'=>'Search KEYWORD / EN / FR'
                        ]); ?>
                    </div>

                    <div class="col-lg-4">
                        <?= $this->Form->control('language',[
                            'options'=> ['All'=>'All','fr_EMPTY'=>'fr_Empty'],
                            'class'=>'form-control',
                            'onchange'=>'this.form.submit()',
                            'label'=>false,
                            'empty'=>'All',

                        ]); ?>
                    </div>
                </div>
                <?= $this->Form->end(); ?>


                <?php if($translations): ?>
                <div class="table-responsive">

                    <table class="table">
                        <thead>
                        <tr>
                            <th>
                                Keyword
                            </th>
                            <th>
                                English
                            </th>
                            <th>
                               French
                            </th>
                            <th style="width: 175px">
                                Date
                            </th>

                        </tr>
                        </thead>
                        <tbody>

                        <?php foreach ($translations as $row): ?>
                            <tr>
                                <td>


                                    <?= $this->Html->link($row['keyword'], [
                                        'controller'=>'translations',
                                        'action'=>'edit',$row['id']
                                    ], ['escape'=>false, 'class'=>'btn btn-primary btn-sm btn-block']); ?>



                                </td>
                                <td><?= $row['en']; ?></td>
                                <td><?= $row['fr']; ?></td>
                                <td>
                                    <?= $row['created']->format('Y-m-d'); ?>
                                    ID: <?= $row['id']; ?>
                                    <?= $this->Html->link("<i class='feather icon-trash-2'></i>", [
                                        'controller'=>'translations',
                                        'action'=>'delete', $row['id']
                                    ], ['escape'=>false, 'class'=>'red-font', 'confirm'=>'Are you sure you want to delete?']); ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>


                        </tbody>
                    </table>

                </div>
                <?php else: ?>
                <div class="text-center"> No Records found</div>
                <?php endif; // ?>
            </div><!-- card-block -->




    </div><!-- /card -->
</div><!-- col-xl-12 -->



<script>
    var searchTimer = null;
    function searchTranslation(){
        //alert('test1')
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
           // alert('test2');
           document.getElementById('translations-form').submit();

        }, 1000);
    }

    function showImport(){
         var element = document.getElementById('translations-import');

        if (element.style.display === "none" || element.style.display === "") {
            element.style.display = "block"; // show
        } else {
            element.style.display = "none";  // hide
        }
    }


</script>
