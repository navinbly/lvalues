<section class="content-add py-5">
    <div class="container">
        <h3>Add Content Node</h3>

        <form method="post" action="#">
            <div class="mb-3">
                <label>Title</label>
                <input type="text" class="form-control" placeholder="e.g. GCP / BigQuery">
            </div>

            <div class="mb-3">
                <label>Parent</label>
                <select class="form-control">
                    <option value="">-- Root (Course) --</option>
                    <option>GCP</option>
                    <option>GCP → Data Engineer</option>
                </select>
            </div>

            <button class="btn btn-success">Save</button>
        </form>
    </div>
</section>
