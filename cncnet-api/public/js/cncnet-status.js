// Fetch Services Status
(function ()
{
    function onGetServices()
    {
        $.ajax({ url: "//cncnet.org/status.json", dataType: 'json', })
            .done((response) => onServicesReceived(response))
            .fail(function (err) { console.log(err) });
    }

    function onServicesReceived(response)
    {
        var services = response.services;
        var el = document.getElementById("services");

        for (var i = 0; i < services.length; i++)
        {
            var service = services[i];
            var li = document.createElement("li");

            updateService(li, service.title, "h4");
            updateService(li, service.status, "span");
            updateService(li, service.description, "p");

            if (service.errors.length > 0)
            {
                li.classList.add("status-errors");

                var ul = document.createElement("ul");
                ul.classList.add("list-unstyled");

                for (var j = 0; j < service.errors.length; j++)
                {
                    var error = service.errors[j];
                    var errorLi = document.createElement("li");

                    var t = updateService(ul, error.title, "h4");
                    var d = updateService(ul, error.description, "p");

                    errorLi.appendChild(t);
                    errorLi.appendChild(d);

                    ul.appendChild(errorLi);
                }

                li.appendChild(ul);
            }
            else
            {
                li.classList.add("status-ok");
            }

            el.appendChild(li);
        }
    }

    function updateService(element, text, type)
    {
        var el = document.createElement(type);
        if (text != null)
        {
            el.innerText = text;
            element.appendChild(el);
        }
        return el;
    }

    onGetServices();
})();
