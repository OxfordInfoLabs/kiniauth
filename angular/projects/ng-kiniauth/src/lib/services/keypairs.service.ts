import {Injectable} from "@angular/core";
import {HttpClient} from "@angular/common/http";
import {ProjectService} from "./project.service";


@Injectable({
    providedIn: 'root'
})
export class KeypairsService {

    constructor(private http: HttpClient,
                private projectService: ProjectService) {
    }

    public list(): Promise<any> {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.get('/account/keypair', {
            params: {projectKey}
        }).toPromise();
    }


    public generate(description = '') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.post('/account/keypair?projectKey=' + projectKey, '"' + description + '"').toPromise();
    }


    public get(id) {
        return this.http.get('/account/keypair/' + id).toPromise();
    }


    public delete(id) {
        return this.http.delete('/account/keypair', {
            params: {id}
        }).toPromise();
    }


}
