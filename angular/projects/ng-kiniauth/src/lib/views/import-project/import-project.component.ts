import {Component} from '@angular/core';
import {ProjectService} from "../../services/project.service";

@Component({
    selector: 'ka-import-project',
    templateUrl: './import-project.component.html',
    styleUrls: ['./import-project.component.sass'],
    standalone: false
})
export class ImportProjectComponent {

    // Files
    private files: any;

    // Analysis
    public analysis: any;

    // Expose object.keys to view
    public keys = Object.keys;

    public success = false;


    constructor(private projectService: ProjectService) {
    }

    /**
     * Analyse import
     *
     */
    async analyseImport(event: any) {
        this.files = Array.from(event.target.files);
        this.analysis = await this.projectService.analyseImport(this.getFileFormData());
    }


    // Perform the import
    async import() {
        await this.projectService.import(this.getFileFormData());
        this.analysis = null;
        this.success = true;
    }

    // Get file form data
    getFileFormData() {
        const formData = new FormData();

        for (const file of this.files) {
            formData.append(file.name, file);
        }

        return formData;
    }


}
