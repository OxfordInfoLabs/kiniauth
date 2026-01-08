import {Injectable} from '@angular/core';
import { HttpClient } from '@angular/common/http';
import {ProjectService} from './project.service';

@Injectable({
    providedIn: 'root'
})
export class ApiKeysService {

    constructor(private http: HttpClient,
                private projectService: ProjectService) {
    }

    public list(): Promise<any> {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.get('/account/apikey', {
            params: {projectKey}
        }).toPromise();
    }

    public create(description = '') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.post('/account/apikey?projectKey=' + projectKey, '"' + description + '"').toPromise();
    }

    public assignableRoles(apiKeyId = null, scope = 'PROJECT', filterString = '', offset = 0, limit = 10000) {
        return this.http.get('/account/apikey/assignableRoles', {
            params: {apiKeyId, scope, filterString, offset, limit}
        }).toPromise();
    }

    public getApiKeyRoles(apiKeyId) {
        return this.http.get('/account/apikey/roles/' + apiKeyId)
            .toPromise();
    }

    public updateAPIKeyScope(roleAssignments, apiKeyId) {
        return this.http.post('/account/apikey/updateAPIKeyScope?apiKeyId=' + apiKeyId, roleAssignments)
            .toPromise();
    }

    public update(id, description) {
        return this.http.put('/account/apikey/' + id, '"' + description + '"').toPromise();
    }

    public regenerate(id) {
        return this.http.put('/account/apikey/regenerate', id).toPromise();
    }

    public suspend(id) {
        return this.http.put('/account/apikey/suspend', id).toPromise();
    }

    public reactivate(id) {
        return this.http.put('/account/apikey/reactivate', id).toPromise();
    }

    public delete(id) {
        return this.http.delete('/account/apikey', {
            params: {id}
        }).toPromise();
    }
}
