import {Component, OnInit} from '@angular/core';
import {ProjectService} from "../../services/project.service";
import {merge, Subject} from "rxjs";
import {debounceTime, switchMap} from "rxjs/operators";

@Component({
    selector: 'ka-export-project',
    templateUrl: './export-project.component.html',
    styleUrls: ['./export-project.component.sass']
})
export class ExportProjectComponent implements OnInit {

    public projectSub = new Subject();
    public exportableResources: any;
    public exportingResources: any = {};

    constructor(private projectService: ProjectService) {
    }

    ngOnInit(): void {
        if (this.projectService) {
            this.projectSub = this.projectService.activeProject;
        }

        this.projectService.getExportableProjectResources().then((resources) => {
            this.exportableResources = resources;
        });
    }

    export() {
        const exportConfig: any = {};
        Object.keys(this.exportingResources).forEach(key => {
            if (this.exportingResources[key]) {
                const exploded = key.split(':');
                if (!Object.keys(exportConfig).includes(exploded[0])) {
                    exportConfig[exploded[0]] = [];
                }
                exportConfig[exploded[0]].push(exploded[1]);
            }
        });
        this.projectService.exportProject(exportConfig);

    }
}
